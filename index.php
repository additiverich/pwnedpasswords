<?php

# Client for pwnedpasswords.com & LastPass
# David Ritchie, 2018
# Some license guff or other goes here

$file = $argv[1];
$client = new PwnedPasswordClient($file);
$client->check_passwords();

class PwnedPasswordClient
{
    private $file = NULL;
    private $cache = [];
    private $credentials = [];

    function __construct($file)
    {
        $this->file = $file;
        $this->read_credentials();
    }

    # Read the CSV one line at a time and populate an array of arrays of column names to values
    private function read_credentials()
    {
        $this->credentials = [];
        $handle = fopen($this->file, "r");

        $names = fgetcsv($handle); // Assume the first line is a header
        while (($data = fgetcsv($handle)) !== FALSE) {
            $this->credentials[] = (array_combine($names, $data));
        }
    }

    # Connect to PP and get the password count for the submitted password.
    # Cache the returned passwords & counts for the sake of speed.
    private function check_password_count($password)
    {
        $hash = strtoupper(sha1($password));
        list($prefix, $suffix) = [ substr($hash, 0, 5), substr($hash, 5) ];

        if(array_key_exists($hash, $this->cache))
        {
            return $this->cache[$hash];
        }

        $result = file_get_contents('https://api.pwnedpasswords.com/range/' . $prefix);
        $rows = explode("\n", $result);
        $counts = [];
        foreach($rows as $row)
        {
            list($s, $count) = explode(':', $row);

            # Cache these to speed up re-used passwords
            $this->cache[$prefix . $s] = $count;
        }

        # Check cache and return value if it's since been populated
        if(array_key_exists($hash, $this->cache))
        {
            return $this->cache[$hash];
        }

        return false;
    }

    # For each credential, query PP and display a message
    function check_passwords()
    {
        foreach($this->credentials as $credential)
        {
            $count = $this->check_password_count($credential['password']);

            if($count)
            {
                echo sprintf('Password for "%s" used %d times ❌' . PHP_EOL, $credential['name'], $count);
            }
            else
            {
                echo sprintf('Password for "%s" is unique. ✅' . PHP_EOL, $credential['name']);
            }
        }
    }
}








