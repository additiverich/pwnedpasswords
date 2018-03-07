<?php

# Client for pwnedpasswords.com & also for parsing LastPass exports
# David Ritchie, 2018
# Some license guff or other goes here

$arg = $argv[1];

if(file_exists($arg))
{
    $client = new LastPassPasswordChecker($file);
    $client->check_passwords($file);
}

else
{
    $client = new PwnedPasswordClient();
    $count = $client->check_password($arg);
    if(!$count)
    {
        echo 'Password is unique!';
    }
    else{
        echo sprintf('Password has %d hits', $count);
    }

    echo PHP_EOL;
}

class LastPassPasswordChecker
{
    private $file = NULL;
    private $credentials = NULL;
    private $ppclient = NULL;

    function __construct($file)
    {
        $this->file = $file;
        $this->read_credentials();

        $this->ppclient = new PwnedPasswordClient();
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

    # For each credential, query PP and display a message
    function check_passwords($file)
    {
        foreach($this->credentials as $credential)
        {
            $count = $this->ppclient->check_password($credential['password']);

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

class PwnedPasswordClient
{
    private $cache = [];

    function __construct() {}

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

    function check_password($password)
    {
        return $this->check_password_count($password);
    }
}