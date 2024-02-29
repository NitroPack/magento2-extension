<?php

namespace NitroPack\NitroPack\Model\FullPageCache;

interface PurgeInterface {
    public function purge($url = null);
}
