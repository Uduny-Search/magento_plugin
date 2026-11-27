<?php
/**
 * Custom Router for Clean Export URLs
 *
 * This router handles URLs like:
 *   /uduny/export/{slug}
 *
 * And routes them to:
 *   Controller/Export/Index.php with slug parameter
 *
 * Without this router, URLs would need to be:
 *   /uduny/export/index/slug/{slug}
 */

declare(strict_types=1);

namespace UnusualDope\Uduny\Controller;

use Magento\Framework\App\Action\Forward;
use Magento\Framework\App\ActionFactory;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\RouterInterface;
use Magento\Framework\Url;

class Router implements RouterInterface
{
    /**
     * @var ActionFactory
     */
    private ActionFactory $actionFactory;

    /**
     * Constructor
     *
     * @param ActionFactory $actionFactory
     */
    public function __construct(
        ActionFactory $actionFactory
    ) {
        $this->actionFactory = $actionFactory;
    }

    /**
     * Match the request
     *
     * @param RequestInterface $request
     * @return ActionInterface|null
     */
    public function match(RequestInterface $request): ?ActionInterface
    {
        if (
            $request->getModuleName() === 'uduny'
            && $request->getControllerName() === 'export'
            && $request->getActionName() === 'index'
        ) {
            return null;
        }

        $identifier = trim((string) $request->getPathInfo(), '/');

        // Match pattern: uduny/export/{slug} (excluding literal "index" action)
        if (preg_match('#^uduny/export/([a-zA-Z0-9_-]+)$#', $identifier, $matches)) {
            $slug = $matches[1];
            if (strtolower($slug) === 'index') {
                return null;
            }

            $request->setModuleName('uduny');
            $request->setControllerName('export');
            $request->setActionName('index');
            $request->setParam('slug', $slug);
            $request->setAlias(Url::REWRITE_REQUEST_PATH_ALIAS, $identifier);

            return $this->actionFactory->create(Forward::class);
        }

        return null;
    }
}
