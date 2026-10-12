<?php declare(strict_types=1);

namespace Movary\Util;

class DnsResolver
{
    /** @return array<int, string> */
    public function resolve(string $host) : array
    {
        $records = dns_get_record($host, DNS_A | DNS_AAAA);
        $addresses = [];
        if ($records !== false) {
            foreach ($records as $record) {
                if (isset($record['ip']) === true) {
                    $addresses[] = $record['ip'];
                }
                if (isset($record['ipv6']) === true) {
                    $addresses[] = $record['ipv6'];
                }
            }
        }

        $ipv4Addresses = gethostbynamel($host);
        if ($ipv4Addresses !== false) {
            $addresses = array_merge($addresses, $ipv4Addresses);
        }

        return array_values(array_unique($addresses));
    }
}
