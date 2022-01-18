<?php


namespace Jazor\WebFarm;


class Cote
{

    /**
     * @var string
     */
    private $location;
    /**
     * @var array
     */
    private $hosts;
    /**
     * @var array|null
     */
    private $indexes;

    public function __construct(string $location, array $hosts, ?array $indexes = null)
    {
        $this->location = $location;
        $this->hosts = $hosts;
        $this->indexes = $indexes ?? ['index.html', 'index.php', ];
    }

    /**
     * @param string|null $subPath
     * @return string
     */
    public function getLocation(?string $subPath = null): string
    {
        if(!empty($subPath)){
            return rtrim($this->location, DIRECTORY_SEPARATOR) .DIRECTORY_SEPARATOR . $subPath;
        }
        return $this->location;
    }

    /**
     * @return array
     */
    public function getHosts(): array
    {
        return $this->hosts;
    }

    /**
     * @return array|null
     */
    public function getIndexes(): ?array
    {
        return $this->indexes;
    }

    public function contains(string $needle){
        foreach ($this->hosts as $host){
            if($host[0] === '*'){
                $subHost = substr($host, 1);
                if(strrpos($needle, $subHost) === strlen($needle) - strlen($subHost)) return true;
                continue;
            }
            if($needle === $host) return true;
        }
        return false;
    }

    public function getIndex(){

        $indexFile = null;
        foreach ($this->indexes as $index){
            $file = $this->getLocation($index);
            if(!is_file($file)) continue;
            $indexFile = $file;
            break;
        }
        return $indexFile;
    }
}
