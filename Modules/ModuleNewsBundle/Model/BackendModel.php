<?php

namespace example\Modules\ModuleNewsBundle\Model;   

use example\Modules\ModuleContactsBundle\Entity\history; 

class BackendModel extends \fireice\FireiceSiteTree\Modules\BasicBundle\Model\BackendModel
{
	protected $bundle_name = 'ModuleNewsBundle';
	protected $entity_name = 'modulenews';    
    
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
    
    /*
    public function getFrontendData( $sitetree_id, $module_id )
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

            // Если этот плагин не присутствует в массиве нужно добавить пустое знаечение
            foreach ($data as &$val)
            {
                if (!array_key_exists($plugin->getValue('name'), $val['data']))
                    $val['data'][$plugin->getValue('name')] = $plugin->getNull();       
            }
        }      

        // Отсортируем в том порядке в каком они указаны в сущности
        foreach ($data as &$value)
        {
            $tmp = $value['data'];
            $value['data'] = array();
                
            foreach ($plugins as $plugin)  
            {
                $value['data'][$plugin->getValue('name')] = $tmp[$plugin->getValue('name')];          
            }                                
        }            

        return array(
            'type' => 'list',
            'data' => array_values($data),           
        );                                                                           
    }    
    */
    
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
        
        //print_r($values); exit;
        
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
        
        unset($data['fireice_order']);

        return $data;
    }    
    
	public function createEdit( $request, $security, $acl )
	{		                                           
        $module = $this->em->getRepository('DialogsBundle:modules')->findOneBy(array('name' => $this->bundle_name));
            
        $service_module = new \fireice\FireiceSiteTree\Dialogs\DialogsBundle\Entity\module();	
        $service_module->setId($module->getId());          
        
        $plugins = $this->getPlugins();
        $plugin_order = (isset($plugins['fireice_order'])) ? $plugins['fireice_order'] : false;
        
        unset($plugins['fireice_order']);        
        
        if ($request->get('id_row') == -1)
        {
            // Если вставка новой новости
            // 
            // Определим следующий row_id
            $query = $this->em->createQuery("
                SELECT 
                    MAX(md.row_id) as maxim
                FROM 
                    ".$this->bundle_name.':'.$this->entity_name." md, 
                    DialogsBundle:moduleslink m_l,
                    DialogsBundle:modulespluginslink mp_l
                WHERE m_l.up_tree = ".$request->get('id')."
                AND m_l.up_module = ".$request->get('module_type')."
                AND m_l.id = mp_l.up_link
                AND mp_l.up_plugin = md.idd");

            $res = $query->getSingleResult(); 

            $curr_row_id = $res['maxim'] + 1;

            // Смотрим есть ли у пользователя право утверждать статьи на уровне главного редактора        
            if ($acl->checkUserPermissions($request->get('id'), $service_module, false, $acl->getValueMask('provemaineditor')))
            {
                // Если есть    
                foreach ($plugins as $plugin)
                {  
                    $plugin_id = $plugin->setDataInDb($request->get($plugin->getValue('name')));    
                
                    $new_module_record = '\\example\\Modules\\'.$this->bundle_name.'\\Entity\\'.$this->entity_name;
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
	                $history->setUpTypeCode($this->entity_name);
                    $history->setActionCode('add_record'); 
		            $this->em->persist($history);
                    $this->em->flush();                
                
                    $new_module_record->setIdd($new_module_record->getId());
                    $new_module_record->setCid($history->getId());    
                    $new_module_record->setFinal('Y');
                    $new_module_record->setStatus('active');  
		            $this->em->persist($new_module_record);
                    $this->em->flush();   
 
                    $modulelink = $this->em->getRepository('DialogsBundle:moduleslink')->findOneBy(array(
                        'up_tree'   => $request->get('id'),
                        'up_module' => $request->get('module_type')
                    ));
                
                    $module_plugin_link = new \fireice\FireiceSiteTree\Dialogs\DialogsBundle\Entity\modulespluginslink();
                    $module_plugin_link->setUpLink($modulelink->getId());
                    $module_plugin_link->setUpPlugin($new_module_record->getIdd());
		            $this->em->persist($module_plugin_link);
                    $this->em->flush();                                                              
                }                                
            }            
            else
            {
                // Если нет
                foreach ($plugins as $plugin)
                {             
                    $plugin_id = $plugin->setDataInDb($request->get($plugin->getValue('name')));    
                
                    $new_module_record = '\\example\\Modules\\'.$this->bundle_name.'\\Entity\\'.$this->entity_name;
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
    	            $history->setUpTypeCode($this->entity_name);
                    $history->setActionCode('edit_record'); 
    		        $this->em->persist($history);
                    $this->em->flush();                
                
                    $new_module_record->setIdd($new_module_record->getId());
                    $new_module_record->setCid($history->getId());
                    $new_module_record->setFinal('W');
                    $new_module_record->setStatus('edit');  
    		        $this->em->persist($new_module_record);
                    $this->em->flush();   
     
                    $modulelink = $this->em->getRepository('DialogsBundle:moduleslink')->findOneBy(array(
                        'up_tree'   => $request->get('id'),
                        'up_module' => $request->get('module_type')
                    ));
                
                    $module_plugin_link = new \fireice\FireiceSiteTree\Dialogs\DialogsBundle\Entity\modulespluginslink();
                    $module_plugin_link->setUpLink($modulelink->getId());
                    $module_plugin_link->setUpPlugin($new_module_record->getIdd());
		            $this->em->persist($module_plugin_link);
                    $this->em->flush();                
                }
            }   
            
            if (false !== $plugin_order)
            {
                // Нужно еще вставить значение order
                $plugin_id = $plugin->setDataInDb($curr_row_id*10);    
                
                $new_module_record = '\\example\\Modules\\'.$this->bundle_name.'\\Entity\\'.$this->entity_name;
                $new_module_record = new $new_module_record();
                $new_module_record->setFinal('Y');
                $new_module_record->setRowId($curr_row_id);
                $new_module_record->setPluginId($plugin_id);
                $new_module_record->setPluginType($plugin_order->getValue('type'));
                $new_module_record->setPluginName($plugin_order->getValue('name'));
                $new_module_record->setStatus('active');
		        $this->em->persist($new_module_record);
                $this->em->flush();    
                
                $history = new history(); 
                $history->setUpUser($security->getToken()->getUser()->getId());
                $history->setUp($new_module_record->getId());
	            $history->setUpTypeCode($this->entity_name);
                $history->setActionCode('add_order'); 
		        $this->em->persist($history);
                $this->em->flush();                
                    
                $new_module_record->setIdd($new_module_record->getId());
                $new_module_record->setCid($history->getId());     
		        $this->em->persist($new_module_record);
                $this->em->flush();   
 
                $modulelink = $this->em->getRepository('DialogsBundle:moduleslink')->findOneBy(array(
                    'up_tree'   => $request->get('id'),
                    'up_module' => $request->get('module_type')
                ));
                
                $module_plugin_link = new \fireice\FireiceSiteTree\Dialogs\DialogsBundle\Entity\modulespluginslink();
                $module_plugin_link->setUpLink($modulelink->getId());
                $module_plugin_link->setUpPlugin($new_module_record->getId());
		        $this->em->persist($module_plugin_link);
                $this->em->flush();   
            }
        }
        else
        {                        
            //$plugins = $this->getPlugins();
            //unset($plugins['fireice_order']);

            // Смотрим есть ли у пользователя право утверждать статьи на уровне главного редактора        
            if ($acl->checkUserPermissions($request->get('id'), $service_module, false, $acl->getValueMask('provemaineditor')))
            {
                // Если есть    
                foreach ($plugins as $plugin)
                {                        
                    $query = $this->em->createQuery("
                        SELECT 
                            md.idd
                        FROM 
                            ".$this->bundle_name.':'.$this->entity_name." md,
                            DialogsBundle:moduleslink m_l,
                            DialogsBundle:modulespluginslink mp_l
                        WHERE md.eid IS NULL            
                        AND m_l.up_tree = ".$request->get('id')."
                        AND m_l.up_module = ".$request->get('module_type')."
                        AND m_l.id = mp_l.up_link
                        AND mp_l.up_plugin = md.idd
                        AND md.final != 'N'
                        AND md.row_id = ".$request->get('id_row')."
                        AND md.plugin_name = '".$plugin->getValue('name')."'
                        AND md.plugin_type = '".$plugin->getValue('type')."'");
        
                    $result = $query->getResult();
            
                    if (count($result) > 0)
                    {
                        $result = $result[0];
                      
                        $plugin_id = $plugin->setDataInDb($request->get($plugin->getValue('name')));
        
                        $history = new history(); 
                        $history->setUpUser($security->getToken()->getUser()->getId());
                        $history->setUp($result['idd']);
	                    $history->setUpTypeCode($this->entity_name);
                        $history->setActionCode('edit_record'); 
		                $this->em->persist($history);
                        $this->em->flush();    
                
                        $hid = $history->getId();  
                    
    	                $query = $this->em->createQuery("UPDATE ".$this->bundle_name.':'.$this->entity_name." md SET md.final='N', md.eid = ".$hid." WHERE md.idd = ".$result['idd']." AND md.final != 'N' AND md.row_id = ".$request->get('id_row'));		 
		                $query->getResult();                  
                
                        $new_module_record = '\\example\\Modules\\'.$this->bundle_name.'\\Entity\\'.$this->entity_name;
                        $new_module_record = new $new_module_record();
                        $new_module_record->setIdd($result['idd']);
                        $new_module_record->setCid($hid);
                        $new_module_record->setFinal('Y');
                        $new_module_record->setRowId($request->get('id_row'));
                        $new_module_record->setPluginId($plugin_id);
                        $new_module_record->setPluginType($plugin->getValue('type'));
                        $new_module_record->setPluginName($plugin->getValue('name'));
                        $new_module_record->setStatus('active');
		                $this->em->persist($new_module_record);
                        $this->em->flush();                          
                    }
                    else
                    {
                        $plugin_id = $plugin->setDataInDb($request->get($plugin->getValue('name')));    
                
                        $new_module_record = '\\example\\Modules\\'.$this->bundle_name.'\\Entity\\'.$this->entity_name;
                        $new_module_record = new $new_module_record();
                        $new_module_record->setFinal('Y');
                        $new_module_record->setRowId($request->get('id_row'));
                        $new_module_record->setPluginId($plugin_id);
                        $new_module_record->setPluginType($plugin->getValue('type'));
                        $new_module_record->setPluginName($plugin->getValue('name'));
                        $new_module_record->setStatus('active');
		                $this->em->persist($new_module_record);
                        $this->em->flush();    
                
                        $history = new history(); 
                        $history->setUpUser($security->getToken()->getUser()->getId());
                        $history->setUp($new_module_record->getId());
	                    $history->setUpTypeCode($this->entity_name);
                        $history->setActionCode('add_record'); 
		                $this->em->persist($history);
                        $this->em->flush();                
                
                        $new_module_record->setIdd($new_module_record->getId());
                        $new_module_record->setCid($history->getId());     
		                $this->em->persist($new_module_record);
                        $this->em->flush();   
 
                        $modulelink = $this->em->getRepository('DialogsBundle:moduleslink')->findOneBy(array(
                            'up_tree'   => $request->get('id'),
                            'up_module' => $request->get('module_type')
                        ));
                
                        $module_plugin_link = new \fireice\FireiceSiteTree\Dialogs\DialogsBundle\Entity\modulespluginslink();
                        $module_plugin_link->setUpLink($modulelink->getId());
                        $module_plugin_link->setUpPlugin($new_module_record->getId());
		                $this->em->persist($module_plugin_link);
                        $this->em->flush(); 
                    }                    
                }                                                                             
            }
            else
            {
                // Если нет
                foreach ($plugins as $plugin)
                {
                    $query = $this->em->createQuery("
                        SELECT 
                            md.idd,
                            md.final 
                        FROM 
                            ".$this->bundle_name.':'.$this->entity_name." md,
                            DialogsBundle:moduleslink m_l,
                            DialogsBundle:modulespluginslink mp_l
                        WHERE (md.final = 'Y' OR md.final = 'W')
                        AND md.eid IS NULL
                        AND md.row_id = ".$request->get('id_row')."
                        AND m_l.up_tree = ".$request->get('id')."
                        AND m_l.up_module = ".$request->get('module_type')."
                        AND m_l.id = mp_l.up_link
                        AND mp_l.up_plugin = md.idd
                        AND md.plugin_name = '".$plugin->getValue('name')."'
                        AND md.plugin_type = '".$plugin->getValue('type')."'");
        
                    $result = $query->getResult();
            
                    if (count($result) > 0)
                    {
                        $result = $result[0];
                      
                        $plugin_id = $plugin->setDataInDb($request->get($plugin->getValue('name')));
        
                        $history = new history(); 
                        $history->setUpUser($security->getToken()->getUser()->getId());
                        $history->setUp($result['idd']);
	                    $history->setUpTypeCode($this->entity_name);
                        $history->setActionCode('edit_record'); 
		                $this->em->persist($history);
                        $this->em->flush();    
            
                        $hid = $history->getId();  
                
    	                if ($result['final'] == 'W')
                            $query = $this->em->createQuery("UPDATE ".$this->bundle_name.':'.$this->entity_name." md SET md.final='N', md.eid = ".$hid." WHERE md.idd = ".$result['idd']." AND (md.final = 'Y' OR md.final = 'W') AND md.eid IS NULL AND md.row_id = ".$request->get('id_row'));		 
		                elseif ($result['final'] == 'Y')
                            $query = $this->em->createQuery("UPDATE ".$this->bundle_name.':'.$this->entity_name." md SET md.eid = ".$hid." WHERE md.idd = ".$result['idd']." AND (md.final = 'Y' OR md.final = 'W') AND md.eid IS NULL AND md.row_id = ".$request->get('id_row'));
                        $query->getResult();
                
                        $new_module_record = '\\example\\Modules\\'.$this->bundle_name.'\\Entity\\'.$this->entity_name;
                        $new_module_record = new $new_module_record();
                        $new_module_record->setIdd($result['idd']);
                        $new_module_record->setCid($hid);
                        $new_module_record->setFinal('W');
                        $new_module_record->setRowId($request->get('id_row'));
                        $new_module_record->setPluginId($plugin_id);
                        $new_module_record->setPluginType($plugin->getValue('type'));
                        $new_module_record->setPluginName($plugin->getValue('name'));
                        $new_module_record->setStatus('edit');
		                $this->em->persist($new_module_record);
                        $this->em->flush();                          
                    } 
                    else
                    {
                        $plugin_id = $plugin->setDataInDb($request->get($plugin->getValue('name')));    
                
                        $new_module_record = '\\example\\Modules\\'.$this->bundle_name.'\\Entity\\'.$this->entity_name;
                        $new_module_record = new $new_module_record();
                        $new_module_record->setFinal('Y');
                        $new_module_record->setRowId($request->get('id_row'));
                        $new_module_record->setPluginId($plugin_id);
                        $new_module_record->setPluginType($plugin->getValue('type'));
                        $new_module_record->setPluginName($plugin->getValue('name'));
                        $new_module_record->setStatus('active');
		                $this->em->persist($new_module_record);
                        $this->em->flush();    
                
                        $history = new history(); 
                        $history->setUpUser($security->getToken()->getUser()->getId());
                        $history->setUp($new_module_record->getId());
	                    $history->setUpTypeCode($this->entity_name);
                        $history->setActionCode('add_record'); 
		                $this->em->persist($history);
                        $this->em->flush();                
                
                        $new_module_record->setIdd($new_module_record->getId());
                        $new_module_record->setCid($history->getId());     
		                $this->em->persist($new_module_record);
                        $this->em->flush();   
 
                        $modulelink = $this->em->getRepository('DialogsBundle:moduleslink')->findOneBy(array(
                            'up_tree'   => $request->get('id'),
                            'up_module' => $request->get('module_type')
                        ));
                
                        $module_plugin_link = new \fireice\FireiceSiteTree\Dialogs\DialogsBundle\Entity\modulespluginslink();
                        $module_plugin_link->setUpLink($modulelink->getId());
                        $module_plugin_link->setUpPlugin($new_module_record->getId());
		                $this->em->persist($module_plugin_link);
                        $this->em->flush(); 
                    }
                }            
            } 
        }
	}       
        
    public function deleteRow($request, $security)
    {       
        foreach ($this->getPlugins() as $plugin)
        {                        
            $query = $this->em->createQuery("
                SELECT 
                    md.idd,
                    md.plugin_id
                FROM 
                    ".$this->bundle_name.':'.$this->entity_name." md,
                    DialogsBundle:moduleslink m_l,
                    DialogsBundle:modulespluginslink mp_l
                WHERE md.eid IS NULL            
                AND m_l.up_tree = ".$request->get('id')."
                AND m_l.up_module = ".$request->get('module_type')."
                AND m_l.id = mp_l.up_link
                AND mp_l.up_plugin = md.idd
                AND md.final != 'N'
                AND md.final != 'D'
                AND md.row_id = ".$request->get('id_row')."
                AND md.plugin_name = '".$plugin->getValue('name')."'
                AND md.plugin_type = '".$plugin->getValue('type')."'");
        
            $result = $query->getResult();
            
            if (count($result) > 0)
            {
                $result = $result[0];   
                
                $plugin_id = $result['plugin_id'];
        
                $history = new history(); 
                $history->setUpUser($security->getToken()->getUser()->getId());
                $history->setUp($result['idd']);
	            $history->setUpTypeCode($this->entity_name);
                $history->setActionCode('delete_new'); 
		        $this->em->persist($history);
                $this->em->flush();    
                
                $hid = $history->getId();  
                    
    	        $query = $this->em->createQuery("UPDATE ".$this->bundle_name.':'.$this->entity_name." md SET md.final='N', md.eid = ".$hid." WHERE md.idd = ".$result['idd']." AND md.final != 'N' AND md.row_id = ".$request->get('id_row'));		 
		        $query->getResult();                  
                
                $new_module_record = '\\example\\Modules\\'.$this->bundle_name.'\\Entity\\'.$this->entity_name;
                $new_module_record = new $new_module_record();
                $new_module_record->setIdd($result['idd']);
                $new_module_record->setCid($hid);
                $new_module_record->setFinal('D');
                $new_module_record->setRowId($request->get('id_row'));
                $new_module_record->setPluginId($plugin_id);
                $new_module_record->setPluginType($plugin->getValue('type'));
                $new_module_record->setPluginName($plugin->getValue('name'));
                $new_module_record->setStatus('deleted');
		        $this->em->persist($new_module_record);
                $this->em->flush();                          
            }
        }               
    }    
    
    public function updateOrders( $request, $security )
    {
        //print_r($request->request->all()); exit;
        
        $orders = $request->get('order');

        // Берем все значения orders какие были
        $query = $this->em->createQuery("
            SELECT 
                md.id,
                md.idd,
                md.row_id,
                md.plugin_id,
                md.status
            FROM 
                ".$this->bundle_name.':'.$this->entity_name." md,
                DialogsBundle:moduleslink m_l,
                DialogsBundle:modulespluginslink mp_l
            WHERE md.eid IS NULL
            AND m_l.up_tree = ".$request->get('id')."
            AND m_l.up_module = ".$request->get('module_type')."
            AND m_l.id = mp_l.up_link
            AND mp_l.up_plugin = md.idd
            AND md.final != 'N'
            AND md.final != 'D'
            AND md.row_id IN (".implode(',', array_keys($orders)).")
            AND md.plugin_name = 'fireice_order'");
        
        $result = $query->getResult();        
        
        // Собираем результат в массив, чтобы индексом было значение 
        // row_id для удобства дальнейшего поиска данных
        $orders_news = array();
        
        foreach ($result as $value)        
            $orders_news[$value['row_id']] = $value;
                    
        //print_r($orders_news); exit;
        
        $plugins = $this->getPlugins();
        $plugin = $plugins['fireice_order'];
        
        // Теперь обходим все полученные аяксом orders`ы и сохраняем их
        foreach ($orders as $key=>$val)
        {
            if (isset($orders_news[$key]))
            {
                // Если была старая запись    
                $plugin_id = $plugin->setDataInDb($val);
        
                $history = new history(); 
                $history->setUpUser($security->getToken()->getUser()->getId());
                $history->setUp($orders_news[$key]['idd']);
	            $history->setUpTypeCode($this->entity_name);
                $history->setActionCode('edit_order'); 
		        $this->em->persist($history);
                $this->em->flush();    
            
                $hid = $history->getId();  
                
                $query = $this->em->createQuery("UPDATE ".$this->bundle_name.':'.$this->entity_name." md SET md.eid = ".$hid." AND md.final = 'N' WHERE md.id = ".$orders_news[$key]['id']." AND md.eid IS NULL AND md.row_id = ".$key);
                
                $new_module_record = '\\example\\Modules\\'.$this->bundle_name.'\\Entity\\'.$this->entity_name;
                $new_module_record = new $new_module_record();
                $new_module_record->setIdd($orders_news[$key]['idd']);
                $new_module_record->setCid($hid);
                $new_module_record->setFinal('Y');
                $new_module_record->setRowId($key);
                $new_module_record->setPluginId($plugin_id);
                $new_module_record->setPluginType($plugin->getValue('type'));
                $new_module_record->setPluginName('fireice_order');
                $new_module_record->setStatus('active');
		        $this->em->persist($new_module_record);
                $this->em->flush();                  
            }
            else
            {
                // Если старых записей не было    
                $plugin_id = $plugin->setDataInDb($val);    
                
                $new_module_record = '\\example\\Modules\\'.$this->bundle_name.'\\Entity\\'.$this->entity_name;
                $new_module_record = new $new_module_record();
                $new_module_record->setFinal('Y');
                $new_module_record->setRowId($key);
                $new_module_record->setPluginId($plugin_id);
                $new_module_record->setPluginType($plugin->getValue('type'));
                $new_module_record->setPluginName($plugin->getValue('name'));
                $new_module_record->setStatus('active');
		        $this->em->persist($new_module_record);
                $this->em->flush();    
                
                $history = new history(); 
                $history->setUpUser($security->getToken()->getUser()->getId());
                $history->setUp($new_module_record->getId());
	            $history->setUpTypeCode($this->entity_name);
                $history->setActionCode('add_order'); 
		        $this->em->persist($history);
                $this->em->flush();                
                
                $new_module_record->setIdd($new_module_record->getId());
                $new_module_record->setCid($history->getId());     
		        $this->em->persist($new_module_record);
                $this->em->flush();   
 
                $modulelink = $this->em->getRepository('DialogsBundle:moduleslink')->findOneBy(array(
                    'up_tree'   => $request->get('id'),
                    'up_module' => $request->get('module_type')
                ));
                
                $module_plugin_link = new \fireice\FireiceSiteTree\Dialogs\DialogsBundle\Entity\modulespluginslink();
                $module_plugin_link->setUpLink($modulelink->getId());
                $module_plugin_link->setUpPlugin($new_module_record->getId());
		        $this->em->persist($module_plugin_link);
                $this->em->flush();                 
            }
        }        
    }
}
