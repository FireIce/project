<?php

namespace project\Modules\Comments\Model;

class FrontendModel extends \fireice\Backend\Modules\Model\FrontendModel
{
    //protected $module_name = 'comments';
    protected $data = array ();

    public function getFrontendData($sitetreeId, $moduleId, $params = array (),$language='ru')
    {
        $configPlugin = 'selectbox';

        // К какому узлу привязан модуль "Комментарии"
        $query = $this->em->createQuery("
            SELECT 
                m_l.up_tree AS id_node,
                md.idd AS id_module
            FROM 
                DialogsBundle:moduleslink m_l,
                DialogsBundle:modules md
            WHERE m_l.up_module = md.idd
            AND md.status = 'active'
            AND md.final = 'Y'
            AND md.name = 'Comments'");

        $result = $query->getSingleResult();

        $idNode = $result['id_node'];
        $idModule = $result['id_module'];

        // Узлы
        $query = $this->em->createQuery("
            SELECT 
                md.row_id
            FROM 
                ModuleCommentsBundle:modulecomments md, 
                FireicePlugins".ucfirst($configPlugin)."Bundle:plugin".$configPlugin." plg_node
            WHERE md.status = 'active'
            AND md.final = 'Y'
            AND md.plugin_name = 'node'
            AND md.plugin_id = plg_node.id
            AND plg_node.value = '".$sitetreeId."'");

        $result = $query->getResult();

        $data = array ();

        if ($result !== array ()) {
            $ids = array ();

            foreach ($result as $value) {
                $ids[] = $value['row_id'];
            }

            $values = array ();

            $plugins = $this->getPlugins();
            unset($plugins['node']);
            unset($plugins['item']);
            //unset($plugins['answer']);

            foreach ($plugins as $plugin) {
                if (!isset($values[$plugin->getValue('type')])) {
                    $values[$plugin->getValue('type')] = $plugin->getData($idNode, $idModule, $language, $this->getBundleName().':'.$this->getEntityName(), self::TYPE_LIST, $ids);
                }
            }

            foreach ($plugins as $plugin) {
                foreach ($values[$plugin->getValue('type')] as $value) {
                    if (!isset($data[$value['row_id']])) {
                        $data[$value['row_id']] = array (
                            'id_row' => $value['row_id'],
                            'data' => array ()
                        );
                    }

                    if ($value['plugin_name'] == $plugin->getValue('name')) {
                        $data[$value['row_id']]['data'][$value['plugin_name']] = $plugin->getValues() + array ('value' => $value['plugin_value']);
                    }
                }

                // Если этот плагин не присутствует в массиве нужно добавить пустое значение
                foreach ($data as &$val) {
                    if (!array_key_exists($plugin->getValue('name'), $val['data'])) $val['data'][$plugin->getValue('name')] = $plugin->getNull();
                }
            }

            $this->data = $data;

            // Отсортируем в том порядке в каком они указаны в сущности
            foreach ($data as &$value) {
                $tmp = $value['data'];
                $value['data'] = array ();

                foreach ($plugins as $plugin) {
                    $value['data'][$plugin->getValue('name')] = $tmp[$plugin->getValue('name')];
                }

                $value['data']['answer']['value'] = ($value['data']['answer']['value'] != '0') ? $data[$value['data']['answer']['value']]['data']['title']['value'].' - '.$data[$value['data']['answer']['value']]['data']['comment']['value'] : '---';
            }
        }

        return array (
            'type' => 'list',
            'data' => $data,
        );
    }

    public function getAnswers()
    {
        $return = array ();

        foreach ($this->data as $key => $value) {
            $return[$key] = $value['data']['title']['value'].' - '.$value['data']['comment']['value'];
        }

        natsort($return);

        return array (0 => '---') + $return;
    }

}
