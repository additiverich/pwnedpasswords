# pwnedpasswords
This is a basic PHP client for testing an exported CSV files from LastPass against Troy Hunt's pwnedpasswords.com service.

Usage:

`php index.php <path to CSV exported from LastPass>`

or

`php index.php <password to check>`

The differentiation between files and passwords is primitive at the moment, so yeah.

I wrote and tested this using PHP 7.2 and 5.6.