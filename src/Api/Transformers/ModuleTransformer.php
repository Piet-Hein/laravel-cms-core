<?php
namespace Czim\CmsCore\Api\Transformers;

use Czim\CmsCore\Contracts\Modules\ModuleInterface;
use League\Fractal\TransformerAbstract;

class ModuleTransformer extends TransformerAbstract
{

    /**
     * @param ModuleInterface $module
     * @return array
     */
    public function transform(ModuleInterface $module)
    {
        return [
            'id'          => $module->getKey(),
            'name'        => $module->getName(),
            'class'       => $module->getAssociatedClass(),
            'links'       => [
                [
                    'rel' => 'self',
                    'uri' => cms()->apiRoute('modules.show', [ $module->getKey() ]),
                ],
            ],
        ];
    }

}
