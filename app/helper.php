<?php

/**
 * Global helper
 */
final class fa
{
    private static $db;
    private static $suffix = '.html';

    /**
     * Get connection
     *
     * @return DB\SQL
     */
    public static function db()
    {
        if (!self::$db) {
            $base = Base::instance();
            $db = $base->get('app.mysql');

            try {
                self::$db = new DB\SQL("mysql:host=$db[host];dbname=$db[name]", $db['user'], $db['password']);
            } catch (Exception $e) {
                $message = "Cannot create database connection, please review your configuration!";
                $base->error(500, $message);
            }
        }

        return self::$db;
    }

    /**
     * Resolve view path
     *
     * @param  string $view
     * @return string
     */
    public static function view($view)
    {
        return str_replace('.', '/', $view).self::$suffix;
    }

    /**
     * Convert namespace to table name
     *
     * @param  string $namespace
     * @return string
     */
    public static function table_name($namespace)
    {
        return Base::instance()->snakecase(lcfirst(substr($namespace, 1+((int) strrpos($namespace, '\\')))));
    }

    /**
     * Handle file upload, cannot handle multiple files
     *
     * @param  string $key          $_FILES[$key]
     * @param  string &$filename
     * @param  array  $allowedTypes
     * @return bool
     */
    public static function handle_file_upload($key, &$filename, $allowedTypes = [])
    {
        $result = false;
        $isArray = isset($_FILES[$key]) && is_array($_FILES[$key]['error']);

        if ($isArray) {
            return $result;
        }

        if (isset($_FILES[$key]) &&
            UPLOAD_ERR_OK === $_FILES[$key]['error'] &&
            ($allowedTypes && in_array($_FILES[$key]['type'], $allowedTypes))) {
            $ext = strtolower(strrchr($_FILES[$key]['name'], '.'));
            $filename .= $ext;
            $result = move_uploaded_file($_FILES[$key]['tmp_name'], $filename);
        }

        return $result;
    }

    /**
     * Say number in indonesian
     * note: this function can exhaust memory if $no greater than 1000000
     * (need improvement)
     *
     * @param  float $no
     * @return string
     */
    public static function terbilang($no)
    {
        if (!is_numeric($no)) {
            return null;
        }

        $strNo = str_replace(',', '.', strval($no));
        $fraction = '0'.(false === ($pos = strpos($strNo, '.'))? '.0':substr($strNo, $pos));
        $no *= 1;
        $minus = 0 > $no;
        $cacah = ['nol','satu','dua','tiga','empat','lima','enam','tujuh','delapan','sembilan','sepuluh','sebelas'];

        $no = abs($no) - $fraction * 1;

        if ($no < 12) {
            $result = $cacah[$no];
        } elseif ($no < 20) {
            $result = $cacah[$no-10].' belas';
        } else if ($no < 100) {
            $mod = $no % 10;
            $mul = floor($no / 10);

            $result = $cacah[$mul].' puluh '.$cacah[$mod];
        } else if ($no < 1000) {
            $mod = $no % 100;
            $mul = floor($no / 100);

            $result = $cacah[$mul].' ratus '.self::terbilang($mod);
        } else if ($no < 100000) {
            $mod = $no % 1000;
            $mul = floor($no / 1000);

            $result = self::terbilang($mul).' ribu '.self::terbilang($mod);
        } else if ($no < 1000000000) {
            $mod = $no % 1000000;
            $mul = floor($no / 1000000);

            $result = self::terbilang($mul).' juta '.self::terbilang($mod);
        } else {
            return $no * ($minus?-1:1);
        }

        $result = ($minus?'minus ':'').str_replace([' nol','satu ','sejuta'], ['','se','satu juta'], $result);

        if ($fraction) {
            for ($i=2, $e=strlen($fraction), $ei=$e-1; $i < $e; $i++) {
                if (2 === $i) {
                    if ($i === $ei && '0' === $fraction[$i]) {
                        break;
                    }
                    $result .= ' koma';
                }
                $result .= ' '.$fraction[$i];
            }
        }

        return $result;
    }

    /**
     * Build path
     *
     * @param  string $path   route or url
     * @param  array  $params
     * @return string
     */
    public static function path($path, array $params = [])
    {
        $base = Base::instance();
        $PARAMS = $base->get('PARAMS');
        unset($PARAMS[0]);
        $params += $PARAMS;

        if (false === strpos($path, '/') && $p = $base->get('ALIASES.'.$path)) {
            $path = ltrim($p,'/');

            $i=0;
            $path=preg_replace_callback('/@(\w+)|\*/',
                function($match) use(&$i,&$params) {
                    $i++;
                    if (isset($match[1]) && array_key_exists($match[1],$params)) {
                        $p = $params[$match[1]];
                        unset($params[$match[1]]);

                        return $p;
                    }

                    $p = array_key_exists($i,$params)?$params[$i]:$match[0];
                    unset($params[$i]);

                    return $p;
                },$path);
            foreach ($PARAMS as $key => $value) {
                unset($params[$key]);
            }
        }

        return '#'===$path[0]?$path:$base->get('BASE').'/'.$path.($params?'?'.http_build_query($params):'');
    }

    public static function microtime($time)
    {
        if (!is_numeric($time)) {
            return null;
        }

        $strNo = str_replace(',', '.', strval($time));
        $fraction = '0'.(false === ($pos = strpos($strNo, '.'))? '.0':substr($strNo, $pos));
        $time *= 1;
        $minus = 0 > $time?'-':'';

        $time = abs($time) - $fraction * 1;
        $fraction = $fraction*1?','.substr(round($fraction, 2), 2):'';

        if ($time < 60) {
            return $minus.$time.$fraction.'s';
        }

        $limits = [
            'm'=>60,
            'h'=>3600,
            'd'=>86400,
            'w'=>604800,
            'mo'=>18144000,
        ];
        arsort($limits);
        $limitsUsed = [];

        foreach ($limits as $key => $value) {
            if ($time <= $value) {
                $limitsUsed[$key] = $value;
            }
        }

        if (count($limits) === count($limitsUsed) && $time > end($limitsUsed)) {
            return '~';
        }

        $str = [];
        foreach ($limits as $key => $value) {
            $mod = floor($time / $value);
            $time %= $value;
            if ($mod) {
                $str[] = $mod.''.$key;
            }
        }
        if ($time || $fraction) {
            $str[] = $time.$fraction.'s';
        }
        $str = implode(' ', $str);

        return $minus.$str;
    }

    public static function dump($message, $halt = false, $cleanPrevious = false)
    {
        if ($cleanPrevious) {
            ob_clean();
        }

        echo '<pre>';
        var_dump($message);
        echo '</pre>';

        if ($halt) {
            die;
        }
    }
}


/**
 * Class Nav
 */
final class nav
{
    private static $activeRoute;
    private static $caret = ['suffix'=>' <b class="caret"></b>'];
    private static $list = ['list'=>['class'=>'dropdown']];
    private static $attr = ['attr'=>['class'=>'dropdown-menu']];
    private static $link = ['link'=>['class'=>'dropdown-toggle','data-toggle'=>'dropdown','role'=>'button','aria-haspopup'=>'true','aria-expanded'=>'false']];

    public static function active($route)
    {
        self::$activeRoute = $route;
    }

    public static function activeRoute()
    {
        if (!self::$activeRoute) {
            $base = Base::instance();

            self::$activeRoute = $base->get('ACTIVE')?:$base->get('ALIAS');
        }

        return self::$activeRoute;
    }

    public static function menuIcon($icon)
    {
        return '<i class="fa fa-'.$icon.'"></i> ';
    }

    public static function attr($route, $icon = null, $identifier = false, array $merge = [])
    {
        $attr = array_merge([
            'route'=>$route,
            'prefix'=>self::menuIcon($icon),
            'identifier'=>$identifier,
        ], $merge);

        return $attr;
    }

    public static function attrParent($icon = null)
    {
        $attr = [
            'url'=>'#',
            'prefix'=>self::menuIcon($icon),
        ] + self::$caret + self::$list + self::$link + self::$attr;

        return $attr;
    }

    public static function left()
    {
        $menu = new app\core\html\Menu(null, ['attr'=>['class'=>'nav navbar-nav']]);
        $menu
            ->setActiveRoute(self::activeRoute())
            ->add('Dashboard', self::attr('dashboard','dashboard'))
            ->getParent()
            ->add('Master', self::attrParent('hdd-o'))
                ->add('Data User', self::attr('crud_index','users',true, ['args'=>['master'=>'user']]))
        ;

        return $menu->render();
    }

    public static function right()
    {
        $menu = new app\core\html\Menu(null, ['attr'=>['class'=>'nav navbar-nav navbar-right']]);
        $menu
            ->setActiveRoute(self::activeRoute())
            ->add('Tools', self::attrParent('cogs'))
                ->add('Profile', self::attr('profile','user'))
                ->getParent()
                ->addDivider()
                ->add('Logout', self::attr('logout','power-off'))
        ;

        return $menu->render();
    }
}

/**
 * Template extending
 */
class ext
{
    public static function getExtensions()
    {
        return [
            'while'=>'_while',
        ];
    }

    public static function _while(array $node)
    {
        $template = Template::instance();
        $attrib=$node['@attrib'];
        unset($node['@attrib']);
        return
            '<?php '.
                (isset($attrib['counter'])?
                    (($ctr=$template->token($attrib['counter'])).'=0; '):'').
                'while (('.$template->token($attrib['true']).')):'.
                (isset($ctr)?(' '.$ctr.'++;'):'').' ?>'.
                $template->build($node).
                '<?php '.$template->token($attrib['then']).'; ?>'.
            '<?php endwhile; ?>';
    }
}

/**
 * Template filter
 */
class filter
{
    public static function getFilters()
    {
        return [
            'bool'=>'bool',
            'rdate'=>'rdate',
            'rdatetime'=>'rdtime',
            'age'=>'age',
        ];
    }

    public static function bool($val)
    {
        return Base::instance()->get($val?'boolean.yes':'boolean.no');
    }

    public static function rdate($date)
    {
        $date = array_filter(explode('-', $date));
        krsort($date);

        return $date?implode('-', $date):null;
    }

    public static function age($date, $format = '%y th')
    {
        if (!$date) {
            return null;
        }

        $now = new DateTime;
        $date = new DateTime($date);
        $diff = $now->diff($date);

        return $diff->format($format);
    }

    public static function rdatetime($date, $format = 'd-m-Y H:i:s')
    {
        if (!$date) {
            return null;
        }

        $date = new DateTime($date);

        return $date->format($format);
    }
}
