<?php

return array(
    'access_key_id' => ENV('ALI_ACCESSKEYID', ''),
    'access_key_secret' => ENV('ALI_ACCESSKEYSECRET', ''),
    'region_id' => ENV('ALI_REGIONID', ''),
    'endpoint' => ENV('ALI_ENDPOINT', ''),
    'bucket' => ENV('ALI_BUCKET', ''),
    'arm' => ENV('ALI_ARM', ''),
    'sts_cache_ttl' => ENV('STS_CACHE_TTL', 3),
    'ali_domain' =>ENV('ALI_DOMAIN',''),
);