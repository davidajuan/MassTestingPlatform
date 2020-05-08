<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

use NeverBounce\Auth;
use NeverBounce\Object\VerificationObject;
use NeverBounce\Single;

class Neverbounce {

    protected $timeout = 3;
    protected $addressinfo = false;
    protected $creditsinfo = false;
    protected $historicalData = false;

    // At the point of entry/realtime lookup, we suggest allowing valid, catchall (accept all / unverifiable),
    // and unknown emails to proceed, while blocking only disposable and invalid emails.
    protected $allow = [
        VerificationObject::VALID,
        VerificationObject::CATCHALL,
        VerificationObject::UNKNOWN,
    ];

    /**
     * Neverbounce is a 3rd party email validation service
     * https://developers.neverbounce.com/v4.0/reference#section-result-codes
     * https://neverbounce.com/help/understanding-and-downloading-results/result-codes
     */
    public function __construct(?array $params = []) {
        Auth::setApiKey(Config::NEVERBOUNCE_API_KEY);
    }

    /**
     * Verify an email address with a 3rd party service neverbounce
     * @return bool true if email is valid, otherwise false
     * @throws Exception Exceptions thrown by neverbounce lib
     */
    public function validEmail(string $email) {
        $verification = Single::check($email, $this->addressinfo, $this->creditsinfo, $this->timeout, $this->historicalData);

        if (in_array($verification->result_integer, $this->allow)) {
            // We found a valid email!
            return true;
        }

        return false;
    }
}
