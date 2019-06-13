# cosmos-crawler
A script to crawl cosmos-sdk nodes. It tries the RPC port on all the ip's in an addressbook.json file. If it can query the net_info rpc call it will then try all the nodes that are connected to that node recursively. All data is then stored in a MySQL database.

This script was used to check for open RPC connections on the various networks. Validators were then warned about this so they could close their RPC ports

## Usage
First create a mysql database and use the crawler.sql file to create the table

Then update the first lines in the crawler.php file:

```
$servername = "server";
$username =   "user";
$password =   "pass";
$database =   "database";
$table =      "table";
$addrbooks =  [ "addrbookcosmos.json", "addrbookiris.json", "addrbookterra.json"];
```

Now run the php file with
``` 
screen php crawler.php 
```

Its recommended to run the crawler in screen because it takes a long time. If you want to keep the logging and run it in the background use:
``` 
screen -L -Logfile crawler.log -dmS crawler php crawler.php 
```

## Using this code
Feel free to use this code for something else entirely, or use it as is. Drop us a note on info@chainlayer.io or find us on Telegram.

## Delegation
If you like what we've been doing please show your love by delegating something to us. Check our site for the latest info (www.chainlayer.io)

