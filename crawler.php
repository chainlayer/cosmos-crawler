<?php
$servername = "server";
$username =   "user";
$password =   "pass";
$database =   "database";
$table =      "table";
$addrbooks =  [ "addrbookcosmos.json", "addrbookiris.json", "addrbookterra.json"];

// Create connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

foreach ($addrbooks as $addrbook) {
  $addrbook = json_decode(file_get_contents($addrbook));
  $a = array();
  foreach ($addrbook->addrs as $addr) {
    $a[$addr->addr->id] = new stdClass();
    $a[$addr->addr->id]->ip = $addr->addr->ip;
    $a[$addr->src->id] = new stdClass();
    $a[$addr->src->id]->ip = $addr->src->ip;
  }
}

// Create the stream context
$context = stream_context_create(array(
    'http' => array(
        'timeout' => 1   // Timeout in seconds
    )
));

$validators = array();

// First try all IPs in the addressbooks and create validator records for them
echo "## Looping through Addressbooks to create validators\n";
foreach ($a as $id=>$node) {
  $validators[$id] = createValidator($id, $node->ip);
  echo "Created validator $id with ip $node->ip for moniker " . $validators[$id]->moniker . "\n";
}

// Now loop through validators as long as there are unprocessed ones
echo "## Looping through unprocessed validators\n";
$unprocessedvalidators = true;
while ($unprocessedvalidators) {
  echo "## Looping through unprocessed validators again " . count($validators) . " in list\n";
  $unprocessedvalidators = false;
  foreach ($validators as $id=>$validator) {
    if ($validator->processed === false) {
      if ($validator->has_netinfo === true) getValidators($validator->netinfo);
      $validators[$id]->processed = true;
      $unprocessedvalidators = true;
    }
  }
}

foreach ($validators as $id=>$validator) {
  insertValidator($id, $validator);
}

function createValidator($id, $ip, $peer = false) {
  global $context, $validators, $a; 
          $starturl = 'http://' . $ip;
          $netinfo = @file_get_contents("$starturl:26657/net_info", 0, $context);
          $status = @file_get_contents("$starturl:26657/status", 0, $context);
          $rpchome = @file_get_contents("$v->rpc_address2:26657/", 0, $context);
          $v = new stdClass();
          $v->id = $id;
          $v->remote_ip = $ip;
          $v->listen_addr = "$starturl:26656";
          $v->rpc_address = "$starturl:26657";
          $v->listen_addr2 = "$starturl:26656";
          $v->rpc_address2 = "$starturl:26657";
          $v->rpchome = '';
	  if ($peer === false) {
            $v->moniker = '';
            $v->network = '';
            $v->version = '';
	  } else {
            $v->moniker = $peer->node_info->moniker;
            $v->network = $peer->node_info->network;
            $v->version = $peer->node_info->version;
	  } 
          $v->tx_index = '2';
          $v->has_netinfo = false;
          $v->has_status = false;
          $v->processed = false;
          if (!empty($status)) {
            $status = json_decode($status);
            $v->has_status = true;
            $v->status = $status;
            $v->validatorstatus = $status->result->validator_info;
            $v->network = $status->result->node_info->network;
            $v->version = $status->result->node_info->version;
            $v->voting_power = $status->result->validator_info->voting_power;
            $v->listen_addr = $status->result->node_info->listen_addr;
            $v->rpc_address = $status->result->node_info->other->rpc_address;
            $v->moniker = $status->result->node_info->moniker;
            $v->tx_index = ($status->result->node_info->other->tx_index=='on'?1:0);
          }
          if (!empty($netinfo)) {
            $netinfo = json_decode($netinfo);
            $v->has_netinfo = true;
            $v->netinfo = $netinfo;
	  }
          if (!empty($rpchome)) {
            $v->rpchome = $rpchome;
          }
          insertValidator($id, $v);
	  return $v;
}


function getValidators($netinfo) {
  global $context, $validators, $a; 

  echo "Parsing " . count($netinfo->result->peers) . " peers from node\n";
  foreach ($netinfo->result->peers as $peer) {
    $id = $peer->node_info->id;
    // Already have this record, lets check if we can update some data
    if (!empty($validators[$peer->node_info->id])) {
      if ($validators[$id]->has_status  === false and 
          $validators[$id]->has_netinfo === false) {
        // Validator info is empty, lets update some stuff
        $validators[$id]->network = $peer->node_info->network;
        $validators[$id]->version = $peer->node_info->version;
        $validators[$id]->moniker = $peer->node_info->moniker;
        $validators[$id]->listen_addr = $peer->node_info->listen_addr;
        $validators[$id]->rpc_address = $peer->node_info->other->rpc_address;
        $validators[$id]->tx_index = ($peer->node_info->other->tx_index=='on'?1:0);
      }
    } else {
      // This is a new Node.. Lets find the IP
      if (filter_var($peer->remote_ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE | FILTER_FLAG_IPV4)) {
        $validators[$id] = createValidator($id, $peer->remote_ip, $peer);
        echo "Created validator $id with ip $t2 for moniker " . $validators[$id]->moniker . "\n";
      } 

      // Try listen_addr
      $t1 = str_replace('tcp://','',$peer->node_info->listen_addr);
      $t2 = str_replace(':26656','',$t1);
      if (filter_var($t2, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE | FILTER_FLAG_IPV4)) {
        $validators[$id] = createValidator($id, $t2, $peer);
        echo "Created validator $id with ip $t2 for moniker " . $validators[$id]->moniker . "\n";
      }

      // Try rpc_address
      $t1 = str_replace('tcp://','',$peer->node_info->other->rpc_address);
      $t2 = str_replace(':26656','',$t1);
      if (filter_var($t2, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE | FILTER_FLAG_IPV4)) {
        $validators[$id] = createValidator($id, $t2, $peer);
        echo "Created validator $id with ip $t2 for moniker " . $validators[$id]->moniker . "\n";
      }
    }
  }
}

function insertValidator($id, $v) {
  global $conn, $table;
  
  if (isset($v->validatorstatus)) {
          $rpcpublic = 1;
          $voting_power = $v->validatorstatus->voting_power;
          $address = $v->validatorstatus->address;
          $validator = ($voting_power > 0?1:0);
  } else {
          $rpcpublic = 0;
          $voting_power = 0;
          $validator = 0;
          $address = '';
  }
  if ($v->has_netinfo) {
          $netinfo = serialize($v->netinfo);
  } else {
          $netinfo = '';
  }
  $query = "replace into $table
              ( id,
                remote_ip,
                listen_addr,
                rpc_address,
                listen_addr2,
                rpc_address2,
                moniker,
                tx_index,
                voting_power,
                address,
                rpcpublic,
                validator,
                lastseen,
                rpchome,
                network,
                version,
                netinfo)
              values(
                '$id',
                '$v->remote_ip',
                '$v->listen_addr',
                '$v->rpc_address',
                '$v->listen_addr2',
                '$v->rpc_address2',
                '$v->moniker',
                '$v->tx_index',
                '$voting_power',
                '$address',
                '$rpcpublic',
                '$validator',
		Now(),
                '$v->rpchome',
                '$v->network',
                '$v->version',
                '$netinfo'
              )";
  if ($conn->query($query) !== TRUE) {
    echo "Error: " . $sql . "<br>" . $conn->error;
  }

}
?>
