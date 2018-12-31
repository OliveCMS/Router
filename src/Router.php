<?php
namespace Olive;

use Olive\Tools;

class Router
{
    protected $hosts = [];

    protected $patern = [];

    protected $notFoundPatern = [];

    protected $lastArgs = [];

    protected $lasthostid;

    public function getHosts()
    {
        return $this->hosts;
    }

    public function setHostID($id)
    {
        $this->lasthostid = $id;
    }

    public function getHostID()
    {
        return $this->lasthostid;
    }

    protected function initRequest($string)
    {
        if ($string == '') {
            $string = '/';
        }
        $string = preg_replace('/(\/+)/', '/', $string);
        $string = parse_url($string);
        $string = $string['path'];
        if (substr($string, 0, 1) != '/') {
            $string = '/' . $string;
        }
        if (substr($string, -1, 1) == '/') {
            $string = substr($string, 0, -1);
        }

        return $string;
    }

    protected function fixURL($url)
    {
        $url = parse_url($url);
        if (! isset($url['host'])) {
            $host = '';
        } else {
            $host = $url['host'];
        }
        if (! isset($url['path'])) {
            $path = '';
        } else {
            $path = $url['path'];
        }

        return preg_replace('/(\/+)/', '/', $host) . $this->initRequest($path);
    }

    public function addHost($url, $id)
    {
        $this->hosts[$id] = $this->fixURL($url);
    }

    public function removeHost($id)
    {
        if (isset($this->hosts[$id])) {
            unset($this->hosts[$id]);
        }
    }

    public function add($request, $call = null, $point = [], $id = '*')
    {
        $this->patern[$id][$this->initRequest($request)] = [
          'call' => $call,
          'point' => $point
        ];
    }

    public function remove($request, $id = '*')
    {
        $request = $this->initRequest($request);
        if (isset($this->patern[$id][$request])) {
            unset($this->patern[$id][$request]);
        }
    }

    public function addNotFound($call = null, $point = [], $id = '*')
    {
        $this->notFoundPatern[$id] = [
          'call' => $call,
          'point' => $point
        ];
    }

    public function removeNotFound($id = '*')
    {
        if (isset($this->notFoundPatern[$id])) {
            unset($this->notFoundPatern[$id]);
        }
    }

    protected function renderHost($string)
    {
        $m = 0;
        $hostid = 0;
        $request = '';
        $string = $this->fixURL($string);
        foreach ($this->hosts as $key => $value) {
            $vo = str_replace('/', '\/', $value);
            if (preg_match('/^' . $vo . '/', $string)) {
                if ($m < strlen($value)) {
                    $m = strlen($value);
                    $hostid = $key;
                    $request = preg_replace('/^' . $vo . '/', '', $string);
                }
            }
        }
        if ($m == 0) {
            return ['hostid' => null, 'request' => $string];
        } else {
            return ['hostid' => $hostid, 'request' => $request];
        }
    }

    protected function validRequest($patern, $request)
    {
        $patern = explode('/', substr($patern, 1));
        $request = explode('/', substr($request, 1));
        $args = [];
        $argtemp = [];
        $argtemp2 = '';
        foreach ($patern as $key => $value) {
            if (isset($patern[$key + 1])) {
                $next = $patern[$key + 1];
            } else {
                $next = null;
            }
            if (isset($request[0]) and $value == $request[0]) {
                $argtemp2 = array_shift($request);
            } elseif ($value == '{}' or ($value == '{+}' and ($next == '{+}' or $next == '{*}'))) {
                if (count($request) == 0) {
                    return false;
                }
                $args[] = array_shift($request);
            } elseif ($value == '{+}' or $value == '{*}') {
                if ($value == '{*}') {
                    if ($next == '{*}' or $next == '{+}') {
                        if (count($request) == 0) {
                            if ($next == '{+}') {
                                return false;
                            } else {
                                $args[] = '';
                            }
                        } else {
                            $args[] = array_shift($request);

                            continue;
                        }
                    }
                    if (count($request) == 0) {
                        $args[] = '';

                        break;
                    }
                    if (! is_null($next) and $request[0] == $next) {
                        $args[] = '';

                        continue;
                    }
                }
                if (is_null($next)) {
                    if ($value == '{+}' and count($request) == 0) {
                        return false;
                    }
                    $args[] = $request;
                    $request = [];
                } else {
                    for ($i = 0; $i <= count($request); $i++) {
                        $argtemp[] = array_shift($request);
                        if (isset($request[0])) {
                            $vnext = $request[0];
                        } else {
                            $vnext = null;
                        }
                        if (is_null($vnext) or $vnext == $next) {
                            break;
                        }
                    }
                    $args[] = $argtemp;
                    $argtemp = [];
                }
            } elseif (! isset($request[0]) or $value != $request[0]) {
                return false;
            }
        }
        if (count($request) != 0) {
            return false;
        }
        $this->lastArgs = $args;

        return true;
    }

    public function render($string)
    {
        $rh = $this->renderHost($string);
        $rhid = $rh['hostid'];
        $this->setHostID($rhid);
        $rhreq = $rh['request'];
        if (is_array($rh)) {
            if (! is_null($rhid) and isset($this->patern[$rhid])) {
                $rreserve = $this->patern[$rhid];
                unset($this->patern[$rhid]);
                array_unshift($this->patern, $rreserve);
            }
            foreach ($this->patern as $id => $paterns) {
                foreach ($paterns as $patern => $data) {
                    if (($id == '*' or (! is_null($rhid) and $rhid == $id)) and $this->validRequest($patern, $rhreq)) {
                        return Tools::runCaller($data['call'], array_merge($data['point'], $this->lastArgs));
                    }
                }
            }
        }
        if (! is_null($rhid)) {
            $f = false;
            if (isset($this->notFoundPatern[$rhid])) {
                $f = true;
                $nfp = $this->notFoundPatern[$rhid];
            } elseif (isset($this->notFoundPatern['*'])) {
                $f = true;
                $nfp = $this->notFoundPatern['*'];
            }
            if ($f == true) {
                return Tools::runCaller($nfp['call'], $nfp['point']);
            }
        }
    }

    public function __construct()
    {
    }
}
