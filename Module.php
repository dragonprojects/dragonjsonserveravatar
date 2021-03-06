<?php
/**
 * @link http://dragonjsonserver.de/
 * @copyright Copyright (c) 2012-2014 DragonProjects (http://dragonprojects.de/)
 * @license http://license.dragonprojects.de/dragonjsonserver.txt New BSD License
 * @author Christoph Herrmann <developer@dragonprojects.de>
 * @package DragonJsonServerAvatar
 */

namespace DragonJsonServerAvatar;

/**
 * Klasse zur Initialisierung des Moduls
 */
class Module
{
    use \DragonJsonServer\ServiceManagerTrait;
	use \DragonJsonServer\EventManagerTrait;
	
    /**
     * Gibt die Konfiguration des Moduls zurück
     * @return array
     */
    public function getConfig()
    {
        return require __DIR__ . '/config/module.config.php';
    }

    /**
     * Gibt die Autoloaderkonfiguration des Moduls zurück
     * @return array
     */
    public function getAutoloaderConfig()
    {
        return [
            'Zend\Loader\StandardAutoloader' => [
                'namespaces' => [
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ],
            ],
        ];
    }
    
    /**
     * Wird bei der Initialisierung des Moduls aufgerufen
     * @param \Zend\ModuleManager\ModuleManager $moduleManager
     */
    public function init(\Zend\ModuleManager\ModuleManager $moduleManager)
    {
    	$sharedManager = $moduleManager->getEventManager()->getSharedManager();
    	$sharedManager->attach('DragonJsonServerApiannotation\Module', 'Request', 
	    	function (\DragonJsonServerApiannotation\Event\Request $eventRequest) {
	    		if (!$eventRequest->getAnnotation() instanceof \DragonJsonServerAvatar\Annotation\Avatar) {
	    			return;
	    		}
	    		$serviceManager = $this->getServiceManager();
	    		$session = $serviceManager->get('\DragonJsonServerAccount\Service\Session')->getSession();
	    		if (null === $session) {
	    			throw new \DragonJsonServer\Exception('missing session');
	    		}
	    		$serviceAvatar = $serviceManager->get('\DragonJsonServerAvatar\Service\Avatar');
	    		$avatar_id = $eventRequest->getRequest()->getParam('avatar_id');
	    		$avatar = $serviceAvatar->getAvatarByAvatarIdAndSession($avatar_id, $session);
				$this->getEventManager()->trigger(
					(new \DragonJsonServerAvatar\Event\LoadAvatar())
						->setTarget($this)
						->setAvatar($avatar)
				);
	    		$serviceAvatar->setAvatar($avatar);
	    	}
    	);
    	$sharedManager->attach('DragonJsonServerApiannotation\Module', 'Servicemap', 
	    	function (\DragonJsonServerApiannotation\Event\Servicemap $eventServicemap) {
	    		if (!$eventServicemap->getAnnotation() instanceof \DragonJsonServerAvatar\Annotation\Avatar) {
	    			return;
	    		}
	    		$eventServicemap->getService()->addParams([
    				[
	                    'type' => 'integer',
	                    'name' => 'avatar_id',
	    				'optional' => false,
    				],
    			]);
	    	}
    	);
    	$sharedManager->attach('DragonJsonServerAccount\Service\Account', 'RemoveAccount', 
	    	function (\DragonJsonServerAccount\Event\RemoveAccount $eventRemoveAccount) {
	    		$account = $eventRemoveAccount->getAccount();
	    		$serviceAvatar = $this->getServiceManager()->get('\DragonJsonServerAvatar\Service\Avatar');
	    		$avatars = $serviceAvatar->getAvatarsByAccountId($account->getAccountId());
	    		foreach ($avatars as $avatar) {
	    			$serviceAvatar->removeAvatar($avatar);
	    		}
	    	}
    	);
    }
}
