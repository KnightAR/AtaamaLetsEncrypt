<?php
require 'vendor/autoload.php';

require_once('./config.inc.php');
require_once('vendor/yourivw/leclient/LEClient/LEClient.php');

foreach($config['certs'] as $cert) {
    $domain = $domains[$cert['domain']];

    // Initiating the client instance. In this case using the staging server (argument 2) and outputting all status and debug information (argument 3).
    $client = new LEClient($configs['email'], LEClient::LE_PRODUCTION, LECLient::LOG_DEBUG);

    $domains = [];

    foreach($cert['hosts'] as $host) {
        if (is_null($host)) {
            $domains[] = $cert['domain'];
        } else {
            $domains[] = sprintf('%s.%s', $host, $cert['domain']);
        }
    }

    if (empty($domains)) {
        continue;
    }

    var_dump($domains);
    //continue;

    // Initiating the order instance. The keys and certificate will be stored in /example.org/ (argument 1) and the domains in the array (argument 2) will be on the certificate.
    $order = $client->getOrCreateOrder($domain['domain'], $domains);
    // Check whether there are any authorizations pending. If that is the case, try to verify the pending authorizations.

    if (!$order->allAuthorizationsValid()) {
        // Get the DNS challenges from the pending authorizations.
        $pending = $order->getPendingAuthorizations(LEOrder::CHALLENGE_TYPE_DNS);
        // Walk the list of pending authorization DNS challenges.
        if (!empty($pending)) {
            $records = [];

            $adapter = null;
            try {
                switch ($domain['provider']) {
                    case 'godaddy':
                        $adapter = new LetsEncrypt\Providers\Godaddy\GodaddyAdapter($domain['domain'], $domain['auth']);
                        break;
                    case 'namecheap':
                        $adapter = new LetsEncrypt\Providers\Namecheap\NamecheapAdapter($domain['domain'],
                            $domain['auth']);
                        break;
                    default:
                        throw new ErrorException("Provider not supported");

                }
            } catch (ErrorException $e) {
                throw new $e;
            } catch (Exception $e) {
                print_r($e);
            }

            foreach ($pending as $challenge) {
                // Create or update the ACME challenge DNS record for this domain
                $records[] = [
                    'name' => '_acme-challenge.' . str_replace('.' . $domain['domain'], '', $challenge['identifier']),
                    'data' => $challenge['DNSDigest'],
                    'ttl' => $adapter->getDefaultTtl(),
                    'type' => \GoDaddyDomainsClient\Model\DNSRecord::TYPE_TXT,
                    'priority' => 10
                ];
            }
            var_dump($records);
            //break;

            if (!empty($records)) {
                if ($adapter->addOrReplaceRecords($records)) {
                    print "Updated DNS records for {$domain['domain']} successful using provider {$domain['provider']} ... moving on" . PHP_EOL;
                    foreach ($pending as $challenge) {
                        // Let LetsEncrypt verify this challenge, which should have been fulfilled in exampleDNSStart.php.
                        $order->verifyPendingOrderAuthorization($challenge['identifier'], LEOrder::CHALLENGE_TYPE_DNS);
                    }

                    // Check once more whether all authorizations are valid before we can finalize the order.
                    if ($order->allAuthorizationsValid()) {
                        // Finalize the order first, if that is not yet done.
                        if (!$order->isFinalized()) {
                            $order->finalizeOrder();
                        }
                        // Check whether the order has been finalized before we can get the certificate. If finalized, get the certificate.
                        if ($order->isFinalized()) {
                            if ($order->getCertificate()) {
                                print "Cert created successfully." . PHP_EOL;
                                exit(0);
                            } else {
                                print "LetsEncrypt Failed to create cert." . PHP_EOL;
                                exit(1);
                            }
                        }
                    } else {
                        print "LetsEncrypt Failed to verify the domains, cert was not created." . PHP_EOL;
                        exit(1);
                    }
                } else {
                    print "Failed updating DNS records for {$domain['domain']} using provider {$domain['provider']}" . PHP_EOL;
                    exit(1);
                }
            }
		}
    }
}

exit(1);