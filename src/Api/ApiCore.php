<?php
namespace Czim\CmsCore\Api;

use Czim\CmsCore\Contracts\Api\ApiCoreInterface;

class ApiCore implements ApiCoreInterface
{

    /**
     * Returns API-formatted response based on given content.
     *
     * @param mixed $content
     * @param int   $statusCode
     * @return mixed
     */
    public function response($content, $statusCode = 200)
    {
        return response()->json($content)->setStatusCode($statusCode);
    }

    /**
     * Returns API-formatted error response.
     *
     * @param mixed $content
     * @param int   $statusCode
     * @return mixed
     */
    public function error($content, $statusCode = 500)
    {
        return response()->json($content)->setStatusCode($statusCode);
    }
}