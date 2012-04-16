<?php

namespace project\Modules\Comments\Model;

use fireice\Backend\Tree\Entity\history;
use fireice\Backend\Dialogs\Entity\module;
use fireice\Backend\Dialogs\Entity\modulespluginslink;

class BackendModel extends \project\Modules\News\Model\BackendModel
{

    //protected $module_name = 'comments';

    public function getBackendData($sitetreeId, $acl, $moduleId, $language = 'ru')
    {
        $values = array ();

        foreach ($this->getPlugins() as $plugin) {
            if (!isset($values[$plugin->getValue('type')])) {
                $values[$plugin->getValue('type')] = $plugin->getData($sitetreeId, $moduleId, $language, $this->getBundleName().':'.$this->getEntityName(), self::TYPE_LIST);
            }
        }

        $data = array ();

        $plugins = $this->getPlugins();

        foreach ($plugins as $plugin) {
            foreach ($values[$plugin->getValue('type')] as $value) {
                if (!isset($data[$value['row_id']])) {
                    $data[$value['row_id']] = array (
                        'id_row' => $value['row_id'],
                        'data' => array ()
                    );
                }

                if ($value['plugin_name'] == $plugin->getValue('name')) $data[$value['row_id']]['data'][$value['plugin_name']] = $plugin->getValues() + array ('value' => $value['plugin_value']);
            }

            // Если этот плагин не присутствует в массиве нужно добавить пустое значение
            foreach ($data as &$val) {
                if (!array_key_exists($plugin->getValue('name'), $val['data'])) $val['data'][$plugin->getValue('name')] = $plugin->getNull();
            }
        }

        if ($data === array ()) {
            $data[0] = array (
                'id_row' => 'null',
                'data' => array ()
            );

            foreach ($plugins as $plugin) $data[0]['data'][$plugin->getValue('name')] = $plugin->getNull();
        } else {
            // Отсортируем в том порядке в каком они указаны в сущности
            foreach ($data as &$value) {

                // Добавим в value плагина answer названия узлов
                $value['data']['answer']['value'] = $this->ajaxLoadComments(
                    array ('id_node' => intval($value['data']['node']['value']), 'id_item' => intval($value['data']['item']['value'])), intval($value['data']['answer']['value'])
                );

                // Добавим в value плагина item названия узлов
                $entity = '\\project\\Modules\\'.ucfirst($this->moduleName).'\\Entity\\'.$this->getEntityName();
                $entity = new $entity();

                $config = $entity->configItem();

                $value['data']['item']['value'] = $this->ajaxLoadList(
                    array ('id_node' => intval($value['data']['node']['value']), 'title' => $config['data']['title'], 'plugin_type' => $value['data']['node']['type']), intval($value['data']['item']['value'])
                );

                // Добавим в value плагина node названия узлов
                $value['data']['node']['value'] = $this->getNodesOptions(
                    $value['data']['node']['value']
                );

                $tmp = $value['data'];
                $value['data'] = array ();

                foreach ($plugins as $plugin) {
                    $value['data'][$plugin->getValue('name')] = $tmp[$plugin->getValue('name')];
                }
            }
        }

        $data = $this->sort($data);

        return array (
            'data' => $data,
        );
    }

    public function getRowData($sitetreeId, $moduleId, $language, $rowId)
    {
        $values = array ();

        foreach ($this->getPlugins() as $plugin) {
            if (!isset($values[$plugin->getValue('type')])) {
                $values[$plugin->getValue('type')] = $plugin->getData($sitetreeId, $moduleId, $language, $this->getBundleName().':'.$this->getEntityName(), self::TYPE_LIST, array ("'".$rowId."'"));
            }
        }

        $data = array ();

        foreach ($this->getPlugins() as $plugin) {
            $type = $plugin->getValue('type');

            if (isset($values[$type]) && $values[$type] !== array ()) {
                foreach ($values[$type] as $val) {
                    if ($val['plugin_name'] == $plugin->getValue('name')) {
                        $data[$plugin->getValue('name')] = $plugin->getValues() + array ('value' => $val['plugin_value']);
                        break;
                    }
                }

                if (!isset($data[$plugin->getValue('name')])) $data[$plugin->getValue('name')] = $plugin->getNull();
            } else {
                $data[$plugin->getValue('name')] = $plugin->getNull();
            }
        }

        // Добавим в value плагина answer начала комментариев
        $data['answer']['value'] = $this->ajaxLoadComments(
            array ('id_node' => intval($data['node']['value']), 'id_item' => intval($data['item']['value'])), intval($data['answer']['value']), $rowId
        );

        // Добавим в value плагина item названия новостей
        $entity = '\\project\\Modules\\'.ucfirst($this->moduleName).'\\Entity\\'.$this->getEntityName();
        $entity = new $entity();

        $config = $entity->configItem();

        $data['item']['value'] = $this->ajaxLoadList(
            array ('id_node' => intval($data['node']['value']), 'title' => $config['data']['title'], 'plugin_type' => $data['node']['type']), intval($data['item']['value'])
        );

        // Добавим в value плагина node названия узлов
        $data['node']['value'] = $this->getNodesOptions(
            $data['node']['value']
        );

        //print_r($data); exit;

        unset($data['fireice_order']);

        return $data;
    }

    public function createEdit($security, $acl)
    {
        $module = $this->em->getRepository('DialogsBundle:modules')->findOneBy(array ('name' => $this->moduleName));

        $serviceModule = new module();
        $serviceModule->setId($module->getId());

        $plugins = $this->getPlugins();

        if ($this->request->get('id_row') == -1) {
            // Если вставка нового комента
            // Определим следующий row_id
            $query = $this->em->createQuery("
                SELECT 
                    MAX(md.row_id) as maxim
                FROM 
                    ".$this->getBundleName().':'.$this->getEntityName()." md, 
                    DialogsBundle:moduleslink m_l,
                    DialogsBundle:modulespluginslink mp_l
                WHERE m_l.up_tree = ".$this->request->get('id')."
                AND m_l.up_module = ".$this->request->get('id_module')."
                AND m_l.id = mp_l.up_link
                AND mp_l.up_plugin = md.idd");

            $res = $query->getSingleResult();

            $currRowId = $res['maxim'] + 1;

            foreach ($plugins as $plugin) {
                $plugin_id = $plugin->setDataInDb($this->request->get($plugin->getValue('name')));

                $newModuleRecord = '\\project\\Modules\\'.ucfirst($this->moduleName).'\\Entity\\'.$this->getEntityName();
                $newModuleRecord = new $newModuleRecord();
                $newModuleRecord->setFinal('T');
                $newModuleRecord->setRowId($currRowId);
                $newModuleRecord->setPluginId($plugin_id);
                $newModuleRecord->setPluginType($plugin->getValue('type'));
                $newModuleRecord->setPluginName($plugin->getValue('name'));
                $newModuleRecord->setStatus('inserting');
                $this->em->persist($newModuleRecord);
                $this->em->flush();

                $history = new history();
                $history->setUpUser($security->getToken()->getUser()->getId());
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
                    'up_tree' => $this->request->get('id'),
                    'up_module' => $this->request->get('id_module')
                    ));

                $modulePluginLink = new modulespluginslink();
                $modulePluginLink->setUpLink($modulelink->getId());
                $modulePluginLink->setUpPlugin($newModuleRecord->getIdd());
                $this->em->persist($modulePluginLink);
                $this->em->flush();
            }
        } else {
            foreach ($plugins as $plugin) {
                $query = $this->em->createQuery("
                        SELECT 
                            md.idd
                        FROM 
                            ".$this->getBundleName().':'.$this->getEntityName()." md,
                            DialogsBundle:moduleslink m_l,
                            DialogsBundle:modulespluginslink mp_l
                        WHERE md.eid IS NULL            
                        AND m_l.up_tree = ".$this->request->get('id')."
                        AND m_l.up_module = ".$this->request->get('id_module')."
                        AND m_l.id = mp_l.up_link
                        AND mp_l.up_plugin = md.idd
                        AND md.final != 'N'
                        AND md.row_id = ".$this->request->get('id_row')."
                        AND md.plugin_name = '".$plugin->getValue('name')."'
                        AND md.plugin_type = '".$plugin->getValue('type')."'");

                $result = $query->getResult();

                if ($result !== array ()) {
                    $result = $result[0];

                    $plugin_id = $plugin->setDataInDb($this->request->get($plugin->getValue('name')));

                    $history = new history();
                    $history->setUpUser($security->getToken()->getUser()->getId());
                    $history->setUp($result['idd']);
                    $history->setUpTypeCode($this->getEntityName());
                    $history->setActionCode('edit_record');
                    $this->em->persist($history);
                    $this->em->flush();

                    $hid = $history->getId();

                    $query = $this->em->createQuery("UPDATE ".$this->getBundleName().':'.$this->getEntityName()." md SET md.final='N', md.eid = ".$hid." WHERE md.idd = ".$result['idd']." AND md.final != 'N' AND md.row_id = ".$this->request->get('id_row'));
                    $query->getResult();

                    $newModuleRecord = '\\project\\Modules\\'.ucfirst($this->moduleName).'\\Entity\\'.$this->getEntityName();
                    $newModuleRecord = new $newModuleRecord();
                    $newModuleRecord->setIdd($result['idd']);
                    $newModuleRecord->setCid($hid);
                    $newModuleRecord->setFinal('Y');
                    $newModuleRecord->setRowId($this->request->get('id_row'));
                    $newModuleRecord->setPluginId($plugin_id);
                    $newModuleRecord->setPluginType($plugin->getValue('type'));
                    $newModuleRecord->setPluginName($plugin->getValue('name'));
                    $newModuleRecord->setStatus('active');
                    $this->em->persist($newModuleRecord);
                    $this->em->flush();
                } else {
                    $plugin_id = $plugin->setDataInDb($this->request->get($plugin->getValue('name')));

                    $newModuleRecord = '\\project\\Modules\\'.ucfirst($this->moduleName).'\\Entity\\'.$this->getEntityName();
                    $newModuleRecord = new $newModuleRecord();
                    $newModuleRecord->setFinal('Y');
                    $newModuleRecord->setRowId($this->request->get('id_row'));
                    $newModuleRecord->setPluginId($plugin_id);
                    $newModuleRecord->setPluginType($plugin->getValue('type'));
                    $newModuleRecord->setPluginName($plugin->getValue('name'));
                    $newModuleRecord->setStatus('active');
                    $this->em->persist($newModuleRecord);
                    $this->em->flush();

                    $history = new history();
                    $history->setUpUser($security->getToken()->getUser()->getId());
                    $history->setUp($newModuleRecord->getId());
                    $history->setUpTypeCode($this->getEntityName());
                    $history->setActionCode('add_record');
                    $this->em->persist($history);
                    $this->em->flush();

                    $newModuleRecord->setIdd($newModuleRecord->getId());
                    $newModuleRecord->setCid($history->getId());
                    $this->em->persist($newModuleRecord);
                    $this->em->flush();

                    $modulelink = $this->em->getRepository('DialogsBundle:moduleslink')->findOneBy(array (
                        'up_tree' => $this->request->get('id'),
                        'up_module' => $this->request->get('id_module')
                        ));

                    $modulePluginLink = new modulespluginslink();
                    $modulePluginLink->setUpLink($modulelink->getId());
                    $modulePluginLink->setUpPlugin($newModuleRecord->getId());
                    $this->em->persist($modulePluginLink);
                    $this->em->flush();
                }
            }
        }
    }

    private function getNodesOptions($idNode)
    {
        $return = array ();

        if ($idNode == '') {
            $return[] = array (
                'value' => '-------',
                'checked' => '1'
            );
        }

        $entity = '\\project\\Modules\\'.ucfirst($this->moduleName).'\\Entity\\'.$this->getEntityName();
        $entity = new $entity();

        $config = $entity->configNode();

        $modules = $config['data']['modules'];
        foreach ($modules as &$value) {
            $value = "'module".$value."'";
        }

        $query = $this->em->createQuery("
            SELECT 
                tr.idd AS node_id,
                md.table_name AS table, 
                md.name AS bundle,
                md.idd AS module_id
            FROM 
                TreeBundle:modulesitetree tr, 
                DialogsBundle:moduleslink md_l, 
                DialogsBundle:modules md
            WHERE md.final = 'Y'
            AND md.status = 'active'
            AND md_l.up_tree = tr.idd
            AND md_l.up_module = md.idd
            AND (tr.status = 'active' OR tr.status = 'hidden')
            AND tr.final = 'Y'
            AND md.type = 'sitetree_node'
            AND tr.idd IN (
                SELECT 
                    tr2.idd
                FROM 
                    TreeBundle:modulesitetree tr2,
                    DialogsBundle:moduleslink md_l2, 
                    DialogsBundle:modules md2                    
                WHERE md2.final = 'Y'
                AND md2.status = 'active'
                AND md_l2.up_tree = tr2.idd
                AND md_l2.up_module = md2.idd
                AND md2.table_name IN (".implode(',', $modules).")
            )");

        $result = $query->getResult();

        $node_types = array ();

        foreach ($result as $val) {
            if (!isset($node_types[$val['table']])) $node_types[$val['table']] = array (
                    'module_id' => $val['module_id'],
                    'bundle' => $val['bundle'],
                    'ids' => array ()
                );

            $node_types[$val['table']]['ids'][] = $val['node_id'];
        }

        $plugins_values = array ();

        foreach ($node_types as $key => $type) {

            $module = '\\project\\Modules\\'.$type['bundle'].'\\Entity\\'.$key;
            $module = new $module();

            foreach ($module->getConfig() as $val) {
                if ($val['name'] === 'fireice_node_title') {
                    $plugin = $val['type'];
                    break;
                }
            }

            $query = $this->em->createQuery("
                SELECT 
                    m_l.up_tree AS node_id,
                    plg.value as node_name
                FROM 
                    Module".$type['bundle'].'Bundle:'.$key." md, 
                    FireicePlugins".ucfirst($plugin)."Bundle:plugin".$plugin." plg,
                    DialogsBundle:moduleslink m_l,
                    DialogsBundle:modulespluginslink mp_l
                WHERE md.status = 'active'
            
                AND m_l.up_tree IN (".implode(',', $type['ids']).")
                AND m_l.up_module = ".$type['module_id']."
                AND m_l.id = mp_l.up_link
                AND mp_l.up_plugin = md.idd

                AND md.final = 'Y'
                AND md.plugin_id = plg.id
                AND md.plugin_name = 'fireice_node_title'");

            $plugins_values = array_merge($query->getResult(), $plugins_values);
        }

        $сhoices = array ();

        foreach ($plugins_values as $value) {
            $сhoices[$value['node_id']] = $value['node_name'];
        }

        ksort($сhoices);

        foreach ($сhoices as $k => $v) {
            $return[$k] = array (
                'value' => $v,
                'checked' => ($idNode == $k) ? '1' : '0'
            );
        }

        return $return;
    }

    public function ajaxLoadComments($data, $idComment = '', $notRow = 0)
    {
        $configPlugin = 'selectbox';

        // Узлы
        $query = $this->em->createQuery("
            SELECT 
                md.row_id
            FROM 
                ModuleCommentsBundle:modulecomments md, 
                FireicePlugins".ucfirst($configPlugin)."Bundle:plugin".$configPlugin." plg_node
            WHERE md.status = 'active'
            AND md.final = 'Y'
            AND md.row_id != '".$notRow."'
            AND md.plugin_name = 'node'
            AND md.plugin_id = plg_node.id
            AND plg_node.value = '".$data['id_node']."'");

        $result = $query->getResult();

        $res = array ();
        $res2 = array ();

        foreach ($result as $val) $res[] = $val['row_id'];

        // Новости
        $query = $this->em->createQuery("
            SELECT 
                md.row_id
            FROM 
                ModuleCommentsBundle:modulecomments md, 
                FireicePlugins".ucfirst($configPlugin)."Bundle:plugin".$configPlugin." plg_item
            WHERE md.status = 'active'
            AND md.final = 'Y'        
            AND md.row_id != '".$notRow."'
            AND md.plugin_name = 'item'
            AND md.plugin_id = plg_item.id
            AND plg_item.value = '".$data['id_item']."'");

        foreach ($query->getResult() as $val) $res2[] = $val['row_id'];

        $res = array_intersect($res, $res2);

        $return = array (
            0 => array (
                'value' => '---',
                'checked' => '0'
            )
        );

        if ($res !== array ()) {
            $query = $this->em->createQuery("
                SELECT 
                    mc.row_id,
                    plg.value AS plugin_value
                FROM 
                    TreeBundle:modulesitetree tr, 
                    DialogsBundle:modulespluginslink as mpl,
                    DialogsBundle:modules mds,
                    DialogsBundle:moduleslink ml, 
                    ModuleCommentsBundle:modulecomments mc, 
                    FireicePluginsTextareaBundle:plugintextarea plg
                WHERE  
                mpl.up_link =  ml.id
                AND mpl.up_plugin = mc.idd
                AND tr.idd = ml.up_tree
                AND ml.up_module = mds.idd     
                AND tr.status != 'deleted'  
                AND tr.final = 'Y'
                AND mds.name = 'Comments'
                AND mc.status = 'active' 
                AND mc.row_id IN (".implode(',', $res).")
                AND mc.final = 'Y' 
                AND mc.plugin_id = plg.id
                AND mc.plugin_name = 'comment'");

            $result = $query->getResult();

            foreach ($result as $v) {
                $return[$v['row_id']] = array (
                    'value' => $v['plugin_value'],
                    'checked' => ($idComment == $v['row_id']) ? '1' : '0'
                );
            }
        }

        return $return;
    }

}
