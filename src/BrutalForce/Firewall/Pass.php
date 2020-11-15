<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace BrutalForce\Firewall;

use BrutalForce\Firewall\GateMan;
use BrutalForce\Request\Call;

class Pass extends GateMan
{

    /**
     *
     * @var string
     */
    private $site_key;

    /**
     *
     * @var string
     */
    private $secret;

    /**
     *
     * @param string $site_key
     * @param string $secret
     */
    public function __construct(string $site_key, string $secret)
    {
        $this->site_key = $site_key;
        $this->secret = $secret;
    }

    /**
     *
     * @param string|null $token
     * @return boolean
     */
    protected function shouldI(?string $token)
    {

        if($this->security())
        {
            return true;
        }
        //inject google string and configuration on the page
        return Call::verifiy($this->secret, $token);
    }

    /**
     *
     * @return string|null
     */
    protected function getToken(): ?string
    {

        return filter_input(INPUT_POST, 'g-recaptcha-response') ?? null;
    }

    /**
     *
     * @param string $site_key
     * @return string
     */
    public static function getScript(string $site_key): string
    {
        ob_start();
        ?>
        <script>

            function onClick(e) {
                e.preventDefault();
                grecaptcha.ready(function () {
                    grecaptcha.execute('<?= $site_key;?>', {action: 'submit'}).then(function (token) {
                        //jquery
                        $ = jQuery;
                        $('form').prepend('<input type="hidden" name="g-recaptcha-response" value="' + token + '">');
                    });
                });
            }

        </script>
        <?php
        ob_clean();
        return ob_get_contents();
    }

    /**
     *
     * @param string $site_key
     * @return string
     */
    public static function getUrl(string $site_key): string
    {
        $part1 = "<script src=\"https://www.google.com/recaptcha/api.js?render=%s\"></script>";
        return sprintf($part1, $site_key);
    }

}
