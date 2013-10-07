<?php
namespace Webfox\MediaFrontend\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Dirk Wenzel <wenzel@webfox01.de>, Agentur Webfox
 *  
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 *
 *
 * @package media_frontend
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 *
 */
class FileCollectionController extends AbstractController {

	/**
	 * fileCollectionRepository
	 *
	 * @var \Webfox\MediaFrontend\Domain\Repository\FileCollectionRepository
	 * @inject
	 */
	protected $fileCollectionRepository;

	/**
	 * action list
	 *
	 * @return void
	 */
	public function listAction() {
		$fileCollections = $this->fileCollectionRepository->findAll();
		$this->view->assign('fileCollections', $fileCollections);
	}

	/**
	 * List File Collections of current Frontend user
	 *
	 * @return void
	 */
	public function listFeUserAction() {
		if($this->frontendUser) {
			if($this->settings['listFeUser']['firstOnly']){
				$fileCollections= array(
					'fileCollection' => $this->fileCollectionRepository->findOneByFrontendUser($this->frontendUser),
				);
				if($this->settings['listFeUser']['redirectFirst']) {
					$this->forward('show', NULL, NULL, $fileCollections);
				}
			} else {		
			$fileCollections = $this->fileCollectionRepository->findByFrontendUser($this->frontendUser);
			}
			$this->view->assign('fileCollections', $fileCollections);
		} else {
			$this->flashMessageContainer->add(\TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('tx_mediafrontend_controller_filecollection.message_log_in_to_see_collections', 'media_frontend'));
		}
	}


	/**
	 * action show
	 *
	 * @param \Webfox\MediaFrontend\Domain\Model\FileCollection $fileCollection
	 * @param \Webfox\MediaFrontend\Domain\Model\Asset $newAsset
	 * @return void
	 * @dontvalidate $newAsset
	 */
	public function showAction(\Webfox\MediaFrontend\Domain\Model\FileCollection $fileCollection, \Webfox\MediaFrontend\Domain\Model\Asset $newAsset = NULL) {
		$this->view->assign('fileCollection', $fileCollection);
		$this->view->assign('newAsset', $newAsset);
	}

	/**
	 * action new
	 *
	 * @param \Webfox\MediaFrontend\Domain\Model\FileCollection $newFileCollection
	 * @dontvalidate $newFileCollection
	 * @return void
	 */
	public function newAction(\Webfox\MediaFrontend\Domain\Model\FileCollection $newFileCollection = NULL) {
		$this->view->assign('newFileCollection', $newFileCollection);
	}

	/**
	 * action create
	 *
	 * @param \Webfox\MediaFrontend\Domain\Model\FileCollection $newFileCollection
	 * @return void
	 */
	public function createAction(\Webfox\MediaFrontend\Domain\Model\FileCollection $newFileCollection) {
	    if ($this->frontendUser) {
		$newFileCollection->setFrontendUser($this->frontendUser);
	    }
	    $storedFile = $this->uploadFile($newFileCollection->getImage());
	    if ($storedFile) {
		$newFileCollection->setImage($storedFile);
		$this->fileCollectionRepository->add($newFileCollection);
		$this->persistenceManager->persistAll();
		$this->fileCollectionRepository->createFileReferences($newFileCollection,
		$storedFile);
	    } else {
		$newFileCollection->setImage(NULL);
		$this->fileCollectionRepository->add($newFileCollection);
	    }
		$this->flashMessageContainer->add(\TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('tx_mediafrontend_controller_filecollection.message_new_collection_created', 'media_frontend'));
	    $this->redirect('list');
	}

	/**
	 * action edit
	 *
	 * @param \Webfox\MediaFrontend\Domain\Model\FileCollection $fileCollection
	 * @return void
	 */
	public function editAction(\Webfox\MediaFrontend\Domain\Model\FileCollection $fileCollection, \Webfox\MediaFrontend\Domain\Model\Asset $newAsset = NULL) {
	    $this->view->assign('fileCollection', $fileCollection);
	    $this->view->assign('newAsset', $newAsset);
	}

	/**
	 * action update
	 *
	 * @param \Webfox\MediaFrontend\Domain\Model\FileCollection $fileCollection
	 */
	public function updateAction(\Webfox\MediaFrontend\Domain\Model\FileCollection $fileCollection) {
	    $storedFile = $this->uploadFile($fileCollection->getImage());
	    if ($storedFile) {
		$fileCollection->setImage($storedFile);
	    }
	    $this->fileCollectionRepository->update($fileCollection);
		$this->flashMessageContainer->add(\TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('tx_mediafrontend_controller_filecollection.message_collection_updated', 'media_frontend'));
		$this->redirect('list');
	}

	/**
	 * action new asset
	 *
	 * @param \Webfox\MediaFrontend\Domain\Model\FileCollection $fileCollection
	 * @param \Webfox\MediaFrontend\Domain\Model\Asset $newAsset
	 * @dontvalidate $newAsset
	 * @return void
	 */
	public function newAssetAction(\Webfox\MediaFrontend\Domain\Model\FileCollection $fileCollection, \Webfox\MediaFrontend\Domain\Model\Asset $newAsset = NULL) {
		$this->view->assign('fileCollection', $fileCollection);
		$this->view->assign('newAsset', $newAsset);
	}
	
	/**
	 * action create asset
	 *
	 * @param \Webfox\MediaFrontend\Domain\Model\FileCollection $fileCollection
	 * @param \Webfox\MediaFrontend\Domain\Model\Asset $newAsset
	 * @return void
	 */
	public function createAssetAction(\Webfox\MediaFrontend\Domain\Model\FileCollection $fileCollection, \Webfox\MediaFrontend\Domain\Model\Asset $newAsset = NULL) {
		if($newAsset) {
		    if ($this->frontendUser) {
			$newAsset->setFrontendUser($this->frontendUser);
		    }
		    $storedFile = $this->uploadFile($newAsset->getFile());
		    if ($storedFile) {
			$newAsset->setFile($storedFile);
			$newAsset->updateMetaData();
			$this->assetRepository->add($newAsset);
			$this->persistenceManager->persistAll();
			$this->assetRepository->createFileReferences($newAsset,
			$storedFile);
		    }
		    $fileCollection->addAsset($newAsset);
			$this->flashMessageContainer->add(\TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('tx_mediafrontend_controller_filecollection.message_asset_added', 'media_frontend'));
		}

		$this->fileCollectionRepository->update($fileCollection);
		$this->redirect('show', NULL, NULL,
			array('fileCollection'=>$fileCollection));
	}

	/**
	 * action delete
	 *
	 * @param \Webfox\MediaFrontend\Domain\Model\FileCollection $fileCollection
	 * @return void
	 */
	public function deleteAction(\Webfox\MediaFrontend\Domain\Model\FileCollection $fileCollection) {
		$this->fileCollectionRepository->remove($fileCollection);
		$this->flashMessageContainer->add(\TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('tx_mediafrontend_controller_filecollection.message_filecollection_removed', 'media_frontend'));
		$this->redirect('list');
	}

}
?>
