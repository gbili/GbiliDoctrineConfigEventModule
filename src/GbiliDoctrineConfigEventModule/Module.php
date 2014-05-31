<?php
namespace GbiliDoctrineConfigEventModule;

class Module
{
    public function onBootstrap($e)
    {
        $sm = $e->getApplication()->getServiceManager();
        $this->injectDoctrineTargetListeners($sm);
    }

    public function injectDoctrineTargetListeners($sm)
    {
        $config = $sm->get('Config');
        $doctrineEventListenersConfig = $config['doctrine_event_listeners'];

        $em = $sm->get('doctrine.entitymanager.orm_default');
        $dem = $em->getEventManager();

        $addedEventListenerHashesToPriority = array();

        foreach ($doctrineEventListenersConfig as $eventIdentifier => $eventListeners) {
            foreach ($eventListeners as $eventListenerSet) {
                $listenerClass = $eventListenerSet['listener_class'];
                $listenerMethod = $eventListenerSet['listener_method'];
                foreach ($eventListenerSet['listeners_params'] as $listenerIdentifierPart => $listenerParams) {
                    $listenerHash = md5($eventIdentifier . $listenerClass . $listenerMethod . $listenerIdentifierPart);
                    $listenerPriority = (isset($eventListenerSet['priority']))
                        ? $eventListenerSet['priority']
                        : 0;
                    if (isset($addedEventListenerHashesToPriority[$listenerHash])) {
                        $lastPriority = $addedEventListenerHashesToPriority[$listenerHash];
                        if ($lastPriority >= $listenerPriority) continue;
                    }

                    $listener = new $listenerClass;
                    call_user_func_array(array($listener, $listenerMethod), $listenerParams);
                    // Last added takes priority over the rest
                    $dem->addEventListener($eventIdentifier, $listener);

                    $addedEventListenerHashes[$listenerHash] = $listenerPriority;
                }
            }
        }
    }
}
