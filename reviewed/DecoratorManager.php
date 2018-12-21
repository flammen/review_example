<?php

    namespace src\Decorator;

    use DateTime;
    use Exception;
    use Psr\Cache\CacheItemPoolInterface;
    use Psr\Log\LoggerInterface;
    use src\Integration\DataProvider;

    class DecoratorManager extends DataProvider
    {
        private $cache;
        private $logger;

        const EXPIRE_PERIOD = '+1 day';

        /**
         * @param string $host
         * @param string $user
         * @param string $password
         * @param CacheItemPoolInterface $cache
         * @param LoggerInterface $logger
         */
        public function __construct($host, $user, $password, CacheItemPoolInterface $cache, LoggerInterface $logger)
        {
            parent::__construct($host, $user, $password);
            $this->cache = $cache;
            $this->logger = $logger;
        }

        /**
         * @param array $input
         *
         * @return array
         */
        public function getResponse(array $input)
        {
            try{
                $cacheKey = $this->getCacheKey($input);
                if ($this->cache->has($cacheKey)) {
                    $cacheItem = $this->cache->getItem($cacheKey);
                    return $cacheItem->get();
                }

                $result = $this->get($input);

                if ($result['successful'] == 1) {
                    $expire_date = (new DateTime())->modify(self::EXPIRE_PERIOD);
                    $cacheItem = $this->cache->getItem($cacheKey);
                    $cacheItem->set($result)->expiresAt($expire_date);
                }

                return $result;
            }catch (Exception $e){
                $this->logger->critical('Error');
            }

            return [];
        }

        /**
         * @param array $input
         *
         * @return string
         */
        public function getCacheKey(array $input)
        {
            return md5(json_encode($input));
        }
    }
