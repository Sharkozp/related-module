<?php

class Sharkozp_Related_Model_Observer {
	private $registryKey = "new_related_product";

	public function saveRelatedProducts($observer) {
		$product          = $observer->getEvent()->getProduct();
		$productId        = $product->getEntityId();
		$originProduct    = Mage::getModel("catalog/product")->load($productId);
		$relatedProductId = Mage::registry($this->registryKey);

		//check product id for broking loop
		if ($relatedProductId == $productId) {
			Mage::unregister($this->registryKey);
		} else {
			$relatedLinkData = $product->getRelatedLinkData();
			//check if we remove products from Related list
			$relatedProductCollection = $originProduct->getRelatedProductCollection()->load();
			$relatedArray             = $relatedProductCollection->getItems();

			//if count of new related products less than from original we remove from related products same product
			if (count($relatedLinkData) < count($relatedArray)) {
				//get removed product ids
				$removedProducts = array_diff_key($relatedArray, $relatedLinkData);
				foreach ($removedProducts as $relatedProductId => $relatedProduct) {
					$relatedProduct = Mage::getModel('catalog/product')->load($relatedProductId);

					$newRelatedArray = array();
					//get previous related collection
					foreach ($relatedProduct->getRelatedLinkCollection() as $link) {
						if ($link->getLinkedProductId() != $productId) {
							$newRelatedArray[ $link->getLinkedProductId() ]['position'] = $link->getPosition();
						}
					}

					//save product id for broking loop
					Mage::register($this->registryKey, $relatedProductId);
					$relatedProduct->setRelatedLinkData($newRelatedArray);
					$relatedProduct->save();
				}
			} else {
				foreach ($relatedLinkData as $relatedProductId => $positions) {
					$relatedProduct  = Mage::getModel('catalog/product')->load($relatedProductId);
					$newRelatedArray = array();
					//get previous related collection
					foreach ($relatedProduct->getRelatedLinkCollection() as $link) {
						$newRelatedArray[ $link->getLinkedProductId() ]['position'] = $link->getPosition();
					}

					//check if product in related list
					if (! array_key_exists($productId, $newRelatedArray)) {
						//save product id for broking loop
						Mage::register($this->registryKey, $relatedProductId);
						$newRelatedArray[ $productId ] = array('position' => '');
						$relatedProduct->setRelatedLinkData($newRelatedArray);
						$relatedProduct->save();
					}
				}
			}
		}
	}

}