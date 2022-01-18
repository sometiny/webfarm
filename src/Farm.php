<?php


namespace Jazor\WebFarm;


class Farm
{
    private array $sites = [];

    private string $base ;
    public function __construct(string $base)
    {
        $this->base = $base;
    }

    public function base(?string $subPath = null): string
    {
        if(!empty($subPath)){
            return rtrim($this->base, DIRECTORY_SEPARATOR) .DIRECTORY_SEPARATOR . $subPath;
        }
        return $this->base;
    }

    /**
     * @param string|Cote $location
     * @param array|string|null $hosts
     * @param array|string|null $indexes
     * @throws \Exception
     */
    public function cote($location, $hosts = null, ?array $indexes = null)
    {
        if($location instanceof Cote){
            array_push($this->sites, $location);
            return;
        }
        if(!is_array($hosts) && !is_string($hosts)) throw new \Exception('[hosts] expect array|string');

        if(is_string($hosts)){
            $hosts = array_map('trim', explode(',', $hosts));
        }


        if($indexes !== null) {
            if(!is_array($indexes) && !is_string($indexes)){
                throw new \Exception('[hosts] expect array|string');
            }

            if(is_string($indexes)){
                $indexes = array_map('trim', explode(',', $indexes));
            }
        }
        $location = $this->base($location);


        array_push($this->sites, new Cote($location, $hosts, $indexes));
    }

    /**
     * @param string $host
     * @return Cote|null
     */
    public function findSite(string $host): ?Cote
    {
        /**
         * @var Cote $site
         */

        foreach ($this->sites as $site) {
            if ($site->contains($host)) return $site;
        }
        return null;
    }

    public function dispatch(?string $host = null, ?string $uri = null){

        $host = $host ?? $_SERVER['HTTP_HOST'];
        $uri = $uri ?? $_SERVER['PATH_INFO'] ?? $_SERVER['REQUEST_URI'];
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? null;

        //PATH_INFO CHECK
        if(preg_match('/^(.+?)\.php(.*?)$/i', $uri, $match)){
            $scriptName = $match[1] . '.php';
            $uri = $match[2] ?? '';
            $_SERVER['SCRIPT_NAME'] = $scriptName;
            $_SERVER['PATH_INFO'] = $uri;
        }

        $idx = strpos($uri, '?');
        if($idx !== false){
            $uri = substr($uri, 0, $idx);
        }

        if(strpos($uri, '..') !== false
            || strpos($scriptName, '..') !== false
        ){
            HttpResponse::end(400, 'dangerous path');
        }
        /**
         * @var Cote $site
         */
        $site = $this->findSite($host);

        if($site === null) {
            HttpResponse::end(404, 'site not found');
        }
        if(!is_dir($site->getLocation())) {
            HttpResponse::end(404, 'site root dir not exists');
        }
        chdir($site->getLocation());

        if($uri === '/'){
            $this->dispatchIndex($site);
            return;
        }


        $realFile = $site->getLocation(ltrim($uri, '/'));

        $idx = strrpos($uri, '.');
        $extension = $idx === false ? null : strtolower(substr($uri, $idx));

        if($extension && $extension === '.php'){
            if(!is_file($realFile)){
                HttpResponse::end(404, 'file not found: ' . $uri);
            }
            include_once $realFile;
            return;
        }

        if($extension){
            if(StaticFile::isStaticFile($extension)){
                StaticFile::send($realFile, $uri);
                return;
            }
            HttpResponse::end(404, 'file not found: ' . $uri);
        }

        $realFile = $site->getLocation($scriptName);
        if(!is_file($realFile)){
            HttpResponse::end(404, 'file not found: ' . $uri);
        }
        include_once $realFile;
    }

    private function dispatchIndex(Cote $site){

        $indexFile = $site->getIndex();

        if($indexFile === null){
            HttpResponse::end(400, 'can not list files');
        }
        if(strrpos($indexFile, '.php') === strlen($indexFile) - 4){
            include_once $indexFile;
            return;
        }
        @readfile($indexFile);
    }
}
