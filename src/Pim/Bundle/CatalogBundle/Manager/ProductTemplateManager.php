<?php

namespace Pim\Bundle\CatalogBundle\Manager;

use Pim\Bundle\CatalogBundle\Doctrine\Common\Persistence\Detacher;
use Pim\Bundle\CatalogBundle\Model\ProductTemplateInterface;
use Pim\Bundle\CatalogBundle\Updater\ProductTemplateUpdaterInterface;
use Pim\Component\Resource\Model\BulkSaverInterface;
use Pim\Bundle\CatalogBundle\Model\ProductInterface;
use Symfony\Component\Validator\ValidatorInterface;

/**
 * Product template manager
 *
 * @author    Nicolas Dupont <nicolas@akeneo.com>
 * @copyright 2014 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 * TODO ProductTemplateApplier ?
 */
class ProductTemplateManager
{
    /** @var ProductTemplateUpdaterInterface */
    protected $templateUpdater;

    /** @var ValidatorInterface */
    protected $productValidator;

    /** @var Detacher */
    protected $productDetacher;

    /** @var BulkSaverInterface */
    protected $productSaver;

    /**
     * @param ProductTemplateUpdaterInterface $templateUpdater
     * @param ValidatorInterface              $productValidator
     * @param Detacher                        $productDetacher
     * @param BulkSaverInterface              $productSaver
     */
    public function __construct(
        ProductTemplateUpdaterInterface $templateUpdater,
        ValidatorInterface $productValidator,
        Detacher $productDetacher,
        BulkSaverInterface $productSaver
    ) {
        $this->templateUpdater  = $templateUpdater;
        $this->productValidator = $productValidator;
        $this->productDetacher  = $productDetacher;
        $this->productSaver     = $productSaver;
    }

    /**
     * @param ProductTemplateInterface $template
     * @param ProductInterface[]       $products
     *
     * @return array $violations
     */
    public function apply(ProductTemplateInterface $template, array $products)
    {
        $this->templateUpdater->update($template, $products);

        $productViolations = [];
        // TODO, perhaps need to extract this part in something more generic,
        // we have a quite close case in EE
        foreach ($products as $product) {
            $violations = $this->productValidator->validate($product);
            $productIdentifier = (string) $product->getIdentifier();
            if ($violations->count() !== 0) {
                $this->productDetacher->detach($product);
                $productViolations[$productIdentifier] = [];
            }
            foreach ($violations as $violation) {
                $productViolations[$productIdentifier][] = sprintf(
                    "%s : %s",
                    $violation->getMessage(),
                    $violation->getInvalidValue()
                );
            }
        }

        // TODO update the versioning context, the update come from variant group !
        $this->productSaver->saveAll($products);

        return $productViolations;
    }
}
