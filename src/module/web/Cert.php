<?php
/*
 * Copyright (C) 2015 Michael Herold <quabla@hemio.de>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace hemio\edentata\module\web;

/**
 * X.509 Certificate
 *
 * @author Michael Herold <quabla@hemio.de>
 */
class Cert
{
    protected $formatted;
    protected $raw;
    protected $parsed;

    const BEGIN = '-----BEGIN CERTIFICATE-----';
    const END   = '-----END CERTIFICATE-----';

    public static function extract($str)
    {
        $pattern = '/'.self::BEGIN.'(?<cert>[a-zA-Z\d+=\/\s]+)'.self::END.'/s';
        $matches = [];
        preg_match_all($pattern, $str, $matches);

        if (count($matches['cert']) > 0) {
            $certs = [];

            foreach ($matches['cert'] as $cert) {
                $certs[] = new Cert(self::clean($cert));
            }
            return $certs;
        }

        return [new Cert(self::clean($str))];
    }

    public static function clean($str)
    {
        return preg_replace('/\s+/', '', $str);
    }

    public function __construct($certificate)
    {
        if ($certificate != self::clean($certificate))
            throw new \hemio\edentata\exception\Error('Expecting cleaned string');

        $this->formatted = self::BEGIN.PHP_EOL.chunk_split($certificate, 64,
                                                           PHP_EOL).self::END;
        $this->raw       = $certificate;

        $this->parsed = openssl_x509_parse($this->formatted, true);

        if (!$this->parsed) {
            throw new \hemio\edentata\exception\Error('Invalid Cert');
        }
    }
    /**
     *
     *         var_dump($parse['subject']['CN']);
      @var_dump($parse['extensions']['subjectAltName']);
      var_dump(extractKeyid($parse['extensions']['authorityKeyIdentifier']));
      var_dump($parse['extensions']['subjectKeyIdentifier']);
      var_dump($parse['validFrom_time_t']);
      var_dump($parse['validTo_time_t']);
      $fp = [
      'md5' => formatFp(openssl_x509_fingerprint($cert, 'md5', false)),
      'sha1' => formatFp(openssl_x509_fingerprint($cert, 'sha1', false)),
      'sha256' => formatFp(openssl_x509_fingerprint($cert, 'sha256', false)),
      'sha512' => formatFp(openssl_x509_fingerprint($cert, 'sha512', false))
      ];

     */

    /**
     *
     * @return \DateTime
     */
    public function validTo()
    {
        return new \DateTime('@'.$this->parsed['validTo_time_t']);
    }

    public function trusted(array $intermediate)
    {
        $itermediateFormatted = array_map(function ($obj) {
            return $obj->formatted();
        }, $intermediate);
        $strInterm = implode(PHP_EOL, $itermediateFormatted);
        $resInterm = tmpfile();
        $pthInterm = stream_get_meta_data($resInterm)['uri'];
        fwrite($resInterm, $strInterm);

        $resCert = tmpfile();
        $pthCert = stream_get_meta_data($resCert)['uri'];
        fwrite($resCert, $this->formatted());

        $status = null;
        $stdout = '';
        exec('/usr/bin/openssl verify -untrusted "'.$pthInterm.'" "'.$pthCert.'"',
             $stdout, $status);

        return $status === 0;
    }

    public function authorityKeyIdentifier()
    {
        $str = $this->parsed['extensions']['authorityKeyIdentifier'];

// extract from keyid:KEY,... format
        $csv = explode(',', $str);
        $key = explode(':', $csv[0], 2);

        return trim($key[1]);
    }

    public function subjectKeyIdentifier()
    {
        return $this->parsed['extensions']['subjectKeyIdentifier'];
    }

    public function raw()
    {
        return $this->raw;
    }

    public function formatted()
    {
        return $this->formatted;
    }

    public function suggestChain(Db $db)
    {
        $ident = $this->authorityKeyIdentifier();

        $chain = [];
        while ($next  = $db->intermediateCertSelect($ident)->fetch()) {
            $chain[] = $next['subject_key_identifier'];
            $ident   = $next['authority_key_identifier'];
        }

        return $chain;
    }
}