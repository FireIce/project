<?php

namespace example\Modules\ModuleCommentsBundle\Model;                   

class BackendModel extends \example\Modules\ModuleNewsBundle\Model\BackendModel
{
	protected $bundle_name = 'ModuleCommentsBundle';
	protected $entity_name = 'modulecomments';
    
    public function getBackendData( $sitetree_id, $acl, $module_id )
    {
        $values = array();
        
        foreach ($this->getPlugins() as $plugin)
        {
            if (!isset($values[$plugin->getValue('type')]))       
            {                
                $values[$plugin->getValue('type')] = $plugin->getBackendModuleData($sitetree_id, $this->bundle_name.':'.$this->entity_name, $module_id, self::TYPE_LIST);
            }            
        }        
          
        $data = array();
        
        $plugins = $this->getPlugins();
        
        foreach ($plugins as $plugin)
        {               
            foreach ($values[$plugin->getValue('type')] as $value)
            {
                if (!isset($data[$value['row_id']]))
                {
                    $data[$value['row_id']] = array(
                        'id_row' => $value['row_id'],
                        'data'   => array()
                    );
                }                   

                if ($value['plugin_name'] == $plugin->getValue('name'))
                    $data[$value['row_id']]['data'][$value['plugin_name']] = $plugin->getValues() + array('value' => $value['plugin_value']);      
            }  

            // Если этот плагин не присутствует в массиве нужно добавить пустое значение
            foreach ($data as &$val)
            {
                if (!array_key_exists($plugin->getValue('name'), $val['data']))
                    $val['data'][$plugin->getValue('name')] = $plugin->getNull();       
            }
        }      

        if (count($data) === 0)
        {
            $data[0] = array(
                'id_row' => 'null',
                'data'   => array()
            );  
            
            foreach ($plugins as $plugin)
                $data[0]['data'][$plugin->getValue('name')] = $plugin->getNull();          
        }
        else
        {
            // Отсортируем в том порядке в каком они указаны в сущности
            foreach ($data as &$value)
            {
                // Добавим в value плагина fireice_comments_new названия узлов
                $value['data']['fireice_comments_new']['value'] = $this->getNewsOptions(intval($value['data']['fireice_comments_node']['value']), intval($value['data']['fireice_comments_new']['value']));

                // Добавим в value плагина fireice_comments_node названия узлов
                $value['data']['fireice_comments_node']['value'] = $this->getNodesOptions($value['data']['fireice_comments_node']['value']);                  
                
                $tmp = $value['data'];
                $value['data'] = array();
                
                foreach ($plugins as $plugin)  
                {
                    $value['data'][$plugin->getValue('name')] = $tmp[$plugin->getValue('name')];          
                }                      
                
              
            }            
        }
               

        $data = $this->sort($data);

        return array(
            'type' => 'list',
            'data' => $data,           
        );                                                                           
    }
    
    public function getRowData($sitetree_id, $module_id, $row_id)
    {
        $values = array();
        
        foreach ($this->getPlugins() as $plugin)
        {
            if (!isset($values[$plugin->getValue('type')]))       
            {                
                $values[$plugin->getValue('type')] = $plugin->getBackendModuleData($sitetree_id, $this->bundle_name.':'.$this->entity_name, $module_id, self::TYPE_LIST, $row_id);
            }            
        }
        
        $data = array(); 
        
        //print_r($values);// exit;
        
        foreach ($this->getPlugins() as $plugin)
        {   
            $type = $plugin->getValue('type');
            
            if (count($values[$type]) > 0)
            {
                foreach ($values[$type] as $val)
                {
                    if ($val['plugin_name'] == $plugin->getValue('name'))
                    {
                        $data[$plugin->getValue('name')] = $plugin->getValues() + array('value' => $val['plugin_value']);
                        break;
                    }
                }
                
                if (!isset($data[$plugin->getValue('name')]))
                    $data[$plugin->getValue('name')] = $plugin->getNull();                  
                
            } else { $data[$plugin->getValue('name')] = $plugin->getNull(); }            
        }
        
        // Добавим в value плагина fireice_comments_answer начала комментариев
        $data['fireice_comments_new']['value'] = $this->getCommentsOptions(
                intval($data['fireice_comments_node']['value']), 
                intval($data['fireice_comments_new']['value']),
                intval($data['fireice_comments_answer']['value'])
        );        
        
        // Добавим в value плагина fireice_comments_new названия новостей
        $data['fireice_comments_new']['value'] = $this->getNewsOptions(
                intval($data['fireice_comments_node']['value']), 
                intval($data['fireice_comments_new']['value'])
        );

        // Добавим в value плагина fireice_comments_node названия узлов
        $data['fireice_comments_node']['value'] = $this->getNodesOptions(
                $data['fireice_comments_node']['value']
        );
        
        //print_r($data); exit;
        
        unset($data['fireice_order']);

        return $data;
    }     
    
    private function getNodesOptions($id_node)
    {
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
            AND md.type = 'sitetree_node'");
        
        $result = $query->getResult();            

        $node_types = array();
        
        foreach ($result as $val)
        {
            if (!isset($node_types[$val['table']]))
                $node_types[$val['table']] = array(
                    'module_id' => $val['module_id'], 
                    'bundle'    => $val['bundle'],
                    'ids'       => array()
                );
            
            $node_types[$val['table']]['ids'][] = $val['node_id'];                         
        }             

        $plugins_values = array();
        
        foreach ($node_types as $key=>$type)
        {
            $module = '\\example\\Modules\\'.$type['bundle'].'\\Entity\\'.$key;
            $module = new $module();

            foreach ($module->getConfig() as $val)
            {
                if ($val['name'] === 'fireice_node_title')
                {
                    $plugin = $val['type'];
                    break;
                }
            }
                
            $query = $this->em->createQuery("
                SELECT 
                    m_l.up_tree AS node_id,
                    plg.value as node_name
                FROM 
                    ".$type['bundle'].':'.$key." md, 
                    FireicePlugins".ucfirst($plugin)."Bundle:plugin".$plugin." plg,
                    DialogsBundle:moduleslink m_l,
                    DialogsBundle:modulespluginslink mp_l
                WHERE md.status = 'active'
            
                AND m_l.up_tree In (".implode(',', $type['ids']).")
                AND m_l.up_module = ".$type['module_id']."
                AND m_l.id = mp_l.up_link
                AND mp_l.up_plugin = md.idd

                AND md.final = 'Y'
                AND md.plugin_id = plg.id
                AND md.plugin_name = 'fireice_node_title'");                
                
            $plugins_values = array_merge($query->getResult(), $plugins_values);
        }                  
              
        $сhoices = array();
            
        foreach ($plugins_values as $value)
        {
            $сhoices[$value['node_id']] = $value['node_name'];
        }
            
        ksort($сhoices);
            
        $return = array();
            
        foreach ($сhoices as $k=>$v)
        {
            $return[$k] = array(
                'value'   => $v,
                'checked' => ($id_node == $k) ? '1':'0'
            );
        }   
            
        return $return;
    }
    
    private function getNewsOptions($id_node, $id_new)
    {
        $plugin_name = 'title';        
        
        if ($id_node == 0)
        {
            return array(0 => array(
                'value'   => '---',
                'checked' => '0'
            ));            
        }
        
        $query = $this->em->createQuery("
            SELECT 
                md.name AS name
            FROM 
                TreeBundle:modulesitetree tr, 
                DialogsBundle:moduleslink md_l, 
                DialogsBundle:modules md
            WHERE md.final = 'Y'
            AND md.status = 'active'
            AND md_l.up_tree = tr.idd
            AND md_l.up_module = md.idd
            AND tr.final = 'Y'
            AND tr.idd='".$id_node."'
            AND md.type = 'user'"); 
        
        $result = $query->getSingleResult();
        
        $config = \Symfony\Component\Yaml\Yaml::parse(__DIR__.'//..//..//'.$result['name'].'//Resources//config//config.yml');
        
        if ($config['parameters']['view'] !== 'list')
        {
            return array(0 => array(
                'value'   => '---',
                'checked' => '0'
            ));
        }
  
        $query = $this->em->createQuery("
            SELECT 
                md.idd as id_module,
                md.name AS name,
                md.table_name as entity
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
            AND tr.idd='".$id_node."'
            AND md.type='user'
            ORDER BY md.type");   
    
        $node_modules = $query->getOneOrNullResult();           
            
        $query = $this->em->createQuery("
            SELECT 
                md.row_id,
                plg.value AS plugin_value
            FROM 
                ".$node_modules['name'].':'.$node_modules['entity']." md, 
                FireicePluginsTextBundle:plugintext plg,
                DialogsBundle:moduleslink m_l,
                DialogsBundle:modulespluginslink mp_l
            WHERE (md.final = 'Y' OR md.final = 'W')
            AND md.eid IS NULL

            AND m_l.up_tree = '".$id_node."'
            AND m_l.up_module = ".$node_modules['id_module']."
            AND m_l.id = mp_l.up_link
            AND mp_l.up_plugin = md.idd

            AND md.plugin_id = plg.id
            AND md.plugin_name = '".$plugin_name."'");           
            
        $сhoices = array();
            
        foreach ($query->getResult() as $val)
        {
            $сhoices[$val['row_id']] = $val['plugin_value'];   
        }                        
        
        $return = array();
        $return[0] = array(
            'value'   => '---',
            'checked' => '0'
        );
            
        foreach ($сhoices as $k=>$v)
        {
            $return[$k] = array(
                'value'   => $v,
                'checked' => ($id_new == $k) ? '1':'0'
            );
        }        
        
        return $return;        
    }
    
    private function getCommentsOptions($id_node, $id_new, $id_comment)
    {
        
    }
}
