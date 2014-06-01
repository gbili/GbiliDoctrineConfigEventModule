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

        $highestPriorityEventListeners = array();
        $eventListenerHashesToPriority = array();

        foreach ($doctrineEventListenersConfig as $eventIdentifier => $eventListeners) {
            foreach ($eventListeners as $eventListenerSet) {
                $listenerClass = $eventListenerSet['listener_class'];
                $listenerMethod = $eventListenerSet['listener_method'];
                foreach ($eventListenerSet['listeners_params'] as $listenerIdentifierPart => $listenerParams) {
                    $listenerPriority = (isset($eventListenerSet['priority']))
                        ? $eventListenerSet['priority']
                        : 0;

                    $listenerHashData = array($eventIdentifier, $listenerClass, $listenerMethod, $listenerIdentifierPart);
                    $listenerHash = md5(implode('', $listenerHashData));
                    $listenerData = $listenerHashData;
                    $listenerData[] = $listenerParams;

                    if (isset($eventListenerHashesToPriority[$listenerHash])) {
                        $lastPriority = $eventListenerHashesToPriority[$listenerHash];
                        if ($lastPriority >= $listenerPriority) continue;
                    }

                    $highestPriorityEventListeners[$listenerHash] = $listenerData;

                    $eventListenerHashesToPriority[$listenerHash] = $listenerPriority;
                }
            }
        }

        foreach ($highestPriorityEventListeners as $eventListenerData) {
            list($eventIdentifier, $listenerClass, $listenerMethod, $listenerIdentifierPart, $listenerParams) = $eventListenerData;
            $listener = new $listenerClass;
            call_user_func_array(array($listener, $listenerMethod), $listenerParams);
            // Last added takes priority over the rest
            $dem->addEventListener($eventIdentifier, $listener);
        }
    }
}
