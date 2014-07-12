curlLib
=======

A PHP Curl Wrapper

```
$myCurl = new curlLib('POST','http://example.com',array('name' => 'John Doe'));
$myCurl->execute();
```

```
$myCurl = new curlLib('POST','http://example.com','[{"name": "John Doe"}]',array('Content-Type: application/json'));
$mycurl->execute($custom_request=TRUE);
```
