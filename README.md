# Ataama Lets Encrypt

## Config Example

```
$configs = [
    'certs' => [],
    'email' => array('exmaple@example.com')
];

$config['certs'][] = [
    'domain' => 'example.com',
    'hosts' => ['test', '*.test']
];

$domains['example.com'] = [
    'provider' => 'godaddy',
    'domain' => 'example.com',
    'auth' => [
        'apikey' => 'apikey',
        'apisecret' => 'apisecret'
    ]
];

$domains['example.net'] = [
    'provider' => 'namecheap',
    'domain' => 'example.net',
    'auth' => [
        'apiuser' => 'apiuser',
        'apikey' => 'apikey'
    ]
];
```