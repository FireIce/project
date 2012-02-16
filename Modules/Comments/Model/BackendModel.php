<?php

namespace project\Modules\Comments\Model;

use fireice\Backend\Tree\Entity\history;
use fireice\Backend\Dialogs\Entity\module;
use fireice\Backend\Dialogs\Entity\modulespluginslink;

class BackendModel extends \project\Modules\News\Model\BackendModel
{
    //protected $module_name = 'comments';

    public function getBackendData($sitetree_id, $acl, $module_id)
    {
        $values = array ();

        foreach ($this->getPlugins() as $plugin) {
            if (!isset($values[$plugin->getValue('type')])) {
                $values[$plugin->getValue('type')] = $plugin->getData($sitetree_id, $this->getBundleName().':'.$this->getEntityName(), $module_id, self::TYPE_LIST);
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

        if (count($data) === 0) {
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
                $entity = '\\project\\Modules\\'.ucfirst($this->module_name).'\\Entity\\'.$this->getEntityName();
                $entity = new $entity();

                $config = $entity->configItem();

                $value['data']['item']['value'] = $this->ajaxLoadList(
                    array ('id_node' => intval($value['data']['node']['value']), 'title' => $config['data']['title']), intval($value['data']['item']['value'])
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

    public function getRowData($sitetree_id, $module_id, $row_id)
    {
        $values = array ();

        foreach ($this->getPlugins() as $plugin) {
            if (!isset($values[$plugin->getValue('type')])) {
                $values[$plugin->getValue('type')] = $plugin->getData($sitetree_id, $this->getBundleName().':'.$this->getEntityName(), $module_id, self::TYPE_LIST, array ("'".$row_id."'"));
            }
        }

        $data = array ();

        foreach ($this->getPlugins() as $plugin) {
            $type = $plugin->getValue('type');

            if (count($values[$type]) > 0) {
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
            array ('id_node' => intval($data['node']['value']), 'id_item' => intval($data['item']['value'])), intval($data['answer']['value']), $row_id
        );

        // Добавим в value плагина item названия новостей
        $entity = '\\project\\Modules\\'.ucfirst($this->module_name).'\\Entity\\'.$this->getEntityName();
        $entity = new $entity();

        $config = $entity->configItem();

        $data['item']['value'] = $this->ajaxLoadList(
            array ('id_node' => intval($data['node']['value']), 'title' => $config['data']['title']), intval($data['item']['value'])
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
        $module = $this->em->getRepository('DialogsBundle:modules')->findOneBy(array ('name' => $this->module_name));

        $service_module = new module();
        $service_module->setId($module->getId());

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

            $curr_row_id = $res['maxim'] + 1;

            foreach ($plugins as $plugin) {
                $plugin_id = $plugin->setDataInDb($this->request->get($plugin->getValue('name')));

                $new_module_record = '\\project\\Modules\\'.ucfirst($this->module_name).'\\Entity\\'.$this->getEntityName();
                $new_module_record = new $new_module_record();
                $new_module_record->setFinal('T');
                $new_module_record->setRowId($curr_row_id);
                $new_module_record->setPluginId($plugin_id);
                $new_module_record->setPluginType($plugin->getValue('type'));
                $new_module_record->setPluginName($plugin->getValue('name'));
                $new_module_record->setStatus('inserting');
                $this->em->persist($new_module_record);
                $this->em->flush();

                $history = new history();
                $history->setUpUser($security->getToken()->getUser()->getId());
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
                    'up_tree' => $this->request->get('id'),
                    'up_module' => $this->request->get('id_module')
                    ));

                $module_plugin_link = new modulespluginslink();
                $module_plugin_link->setUpLink($modulelink->getId());
                $module_plugin_link->setUpPlugin($new_module_record->getIdd());
                $this->em->persist($module_plugin_link);
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

                if (count($result) > 0) {
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

                    $new_module_record = '\\project\\Modules\\'.ucfirst($this->module_name).'\\Entity\\'.$this->getEntityName();
                    $new_module_record = new $new_module_record();
                    $new_module_record->setIdd($result['idd']);
                    $new_module_record->setCid($hid);
                    $new_module_record->setFinal('Y');
                    $new_module_record->setRowId($this->request->get('id_row'));
                    $new_module_record->setPluginId($plugin_id);
                    $new_module_record->setPluginType($plugin->getValue('type'));
                    $new_module_record->setPluginName($plugin->getValue('name'));
                    $new_module_record->setStatus('active');
                    $this->em->persist($new_module_record);
                    $this->em->flush();
                } else {
                    $plugin_id = $plugin->setDataInDb($this->request->get($plugin->getValue('name')));

                    $new_module_record = '\\project\\Modules\\'.ucfirst($this->module_name).'\\Entity\\'.$this->getEntityName();
                    $new_module_record = new $new_module_record();
                    $new_module_record->setFinal('Y');
                    $new_module_record->setRowId($this->request->get('id_row'));
                    $new_module_record->setPluginId($plugin_id);
                    $new_module_record->setPluginType($plugin->getValue('type'));
                    $new_module_record->setPluginName($plugin->getValue('name'));
                    $new_module_record->setStatus('active');
                    $this->em->persist($new_module_record);
                    $this->em->flush();

                    $history = new history();
                    $history->setUpUser($security->getToken()->getUser()->getId());
                    $history->setUp($new_module_record->getId());
                    $history->setUpTypeCode($this->getEntityName());
                    $history->setActionCode('add_record');
                    $this->em->persist($history);
                    $this->em->flush();

                    $new_module_record->setIdd($new_module_record->getId());
                    $new_module_record->setCid($history->getId());
                    $this->em->persist($new_module_record);
                    $this->em->flush();

                    $modulelink = $this->em->getRepository('DialogsBundle:moduleslink')->findOneBy(array (
                        'up_tree' => $this->request->get('id'),
                        'up_module' => $this->request->get('id_module')
                        ));

                    $module_plugin_link = new modulespluginslink();
                    $module_plugin_link->setUpLink($modulelink->getId());
                    $module_plugin_link->setUpPlugin($new_module_record->getId());
                    $this->em->persist($module_plugin_link);
                    $this->em->flush();
                }
            }
        }
    }

    private function getNodesOptions($id_node)
    {
        $return = array ();

        if ($id_node == '') {
            $return[] = array (
                'value' => '-------',
                'checked' => '1'
            );
        }

        $entity = '\\project\\Modules\\'.ucfirst($this->module_name).'\\Entity\\'.$this->getEntityName();
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
                'checked' => ($id_node == $k) ? '1' : '0'
            );
        }

        return $return;
    }

    public function ajaxLoadComments($data, $id_comment='', $not_row=0)
    {
        $config_plugin = 'selectbox';

        // Узлы
        $query = $this->em->createQuery("
            SELECT 
                md.row_id
            FROM 
                ModuleCommentsBundle:modulecomments md, 
                FireicePlugins".ucfirst($config_plugin)."Bundle:plugin".$config_plugin." plg_node
            WHERE md.status = 'active'
            AND md.final = 'Y'
            AND md.row_id != '".$not_row."'
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
                FireicePlugins".ucfirst($config_plugin)."Bundle:plugin".$config_plugin." plg_item
            WHERE md.status = 'active'
            AND md.final = 'Y'        
            AND md.row_id != '".$not_row."'
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

        if (count($res) > 0) {
            $query = $this->em->createQuery("
                SELECT 
                    md.row_id,
                    plg.value AS plugin_value
                FROM 
                    ModuleCommentsBundle:modulecomments md, 
                    FireicePluginsTextareaBundle:plugintextarea plg
                WHERE md.status = 'active'            
                AND md.row_id IN (".implode(',', $res).")
                AND md.final = 'Y' 
                AND md.plugin_id = plg.id
                AND md.plugin_name = 'comment'");

            $result = $query->getResult();

            foreach ($result as $v) {
                $return[$v['row_id']] = array (
                    'value' => $v['plugin_value'],
                    'checked' => ($id_comment == $v['row_id']) ? '1' : '0'
                );
            }
        }

        return $return;
    }

}
