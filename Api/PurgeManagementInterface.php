<?php

namespace NitroPack\NitroPack\Api;

/**
 * PurgeManagement Interface
 *
 * @api
 */
interface PurgeManagementInterface
{
    /**
     * @param $productIds
     * @return mixed
     */
    public function purgeByProductIds($productIds);

    /**
     * @param $categoryIds
     * @return mixed
     */
    public function purgeBycategoryIds($categoryIds);

    /**
     * @param $tags
     * @return mixed
     */
    public function purgeByCacheTags($tags);

    /**
     * @param $urls
     * @return mixed
     */
    public function purgeByUrl($urls);

    /**
     * @return mixed
     */
    public function purgeAll();
}
