<?php

namespace example\Modules\Mails\Model;

use fireice\Backend\Tree\Entity\history;
use fireice\Backend\Dialogs\Entity\modulespluginslink;

class FrontendModel extends \example\Modules\News\Model\FrontendModel
{
    //protected $module_name = 'mails';

    public function saveMessage($id_node, $id_module, $feedback, $acl)
    { 
        $plugins = $this->getPlugins();

        // Определим следующий row_id
        $query = $this->em->createQuery("
                SELECT 
                    MAX(md.row_id) as maxim
                FROM 
                    ".$this->getBundleName().':'.$this->getEntityName()." md, 
                    DialogsBundle:moduleslink m_l,
                    DialogsBundle:modulespluginslink mp_l
                WHERE m_l.up_tree = :up_tree
                AND m_l.up_module = :up_module
                AND m_l.id = mp_l.up_link
                AND mp_l.up_plugin = md.idd");
        
        $query->setParameters(array(
            'up_tree' => $id_node,
            'up_module' => $id_module
        ));

        $res = $query->getSingleResult();

        $curr_row_id = $res['maxim'] + 1;

        foreach ($plugins as $plugin) {
            
            $method = 'get'.ucfirst($plugin->getValue('name'));
            
            $plugin_id = $plugin->setDataInDb($feedback->$method());

            $new_module_record = $this->getModuleEntity();
            $new_module_record->setFinal('T');
            $new_module_record->setRowId($curr_row_id);
            $new_module_record->setPluginId($plugin_id);
            $new_module_record->setPluginType($plugin->getValue('type'));
            $new_module_record->setPluginName($plugin->getValue('name'));
            $new_module_record->setStatus('inserting');
            $this->em->persist($new_module_record);
            $this->em->flush();

            $history = new history();
            $history->setUpUser($acl->current_user->getId());
            $history->setUp($new_module_record->getId());
            $history->setUpTypeCode($this->getEntityName());
            $history->setActionCode('add_record');
            $this->em->persist($history);
            $this->em->flush();

            $new_module_record->setIdd($new_module_record->getId());
            $new_module_record->setCid($history->getId());
            $new_module_record->setFinal('Y');
            $new_module_record->setStatus('active');
            $this->em->persist($new_module_record);
            $this->em->flush();

            $modulelink = $this->em->getRepository('DialogsBundle:moduleslink')->findOneBy(array (
                'up_tree' => $id_node,
                'up_module' => $id_module
                ));

            $module_plugin_link = new modulespluginslink();
            $module_plugin_link->setUpLink($modulelink->getId());
            $module_plugin_link->setUpPlugin($new_module_record->getIdd());
            $this->em->persist($module_plugin_link);
            $this->em->flush();
        }        
    }
}
