<?php

namespace project\Modules\Mails\Model;

use fireice\Backend\Tree\Entity\history;
use fireice\Backend\Dialogs\Entity\modulespluginslink;

class FrontendModel extends \project\Modules\News\Model\FrontendModel
{

    //protected $module_name = 'mails';

    public function saveMessage($idNode, $idModule, $language, $feedback, $acl)
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
                AND m_l.language = :language
                AND m_l.id = mp_l.up_link
                AND mp_l.up_plugin = md.idd");

        $query->setParameters(array (
            'up_tree' => $idNode,
            'up_module' => $idModule,
            'language' => $language,
        ));

        $res = $query->getSingleResult();

        $curr_row_id = $res['maxim'] + 1;

        foreach ($plugins as $plugin) {

            $method = 'get'.ucfirst($plugin->getValue('name'));

            $pluginId = $plugin->setDataInDb($feedback->$method());

            $newModuleRecord = $this->getModuleEntity();
            $newModuleRecord->setFinal('T');
            $newModuleRecord->setRowId($curr_row_id);
            $newModuleRecord->setPluginId($pluginId);
            $newModuleRecord->setPluginType($plugin->getValue('type'));
            $newModuleRecord->setPluginName($plugin->getValue('name'));
            $newModuleRecord->setStatus('inserting');
            $this->em->persist($newModuleRecord);
            $this->em->flush();

            $history = new history();
            $history->setUpUser($acl->current_user->getId());
            $history->setUp($newModuleRecord->getId());
            $history->setUpTypeCode($this->getEntityName());
            $history->setActionCode('add_record');
            $this->em->persist($history);
            $this->em->flush();

            $newModuleRecord->setIdd($newModuleRecord->getId());
            $newModuleRecord->setCid($history->getId());
            $newModuleRecord->setFinal('Y');
            $newModuleRecord->setStatus('active');
            $this->em->persist($newModuleRecord);
            $this->em->flush();

            $modulelink = $this->em->getRepository('DialogsBundle:moduleslink')->findOneBy(array (
            'up_tree' => $idNode,
            'up_module' => $idModule,
            'language' => $language,
            ));

            $modulePluginLink = new modulespluginslink();
            $modulePluginLink->setUpLink($modulelink->getId());
            $modulePluginLink->setUpPlugin($newModuleRecord->getIdd());
            $this->em->persist($modulePluginLink);
            $this->em->flush();
        }
    }

}
