<?php

namespace example\Modules\News\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class FrontendController extends \fireice\Backend\Modules\Controller\FrontendController
{
    protected $model = '\\example\\Modules\\News\\Model\\FrontendModel';
    // Кол-во новостей на странице
    protected $page_limit = 3;

    public function frontend($param, $other=array ())
    {
        $model = $this->getModel();

        $page = 1;
        $url = str_replace($param, '', trim($this->get('request')->getUri(), '/'));
        $url = trim($url, '/');

        $parametres = array ('url' => $url);

        if ($param !== '') {

            foreach ($this->getAvailableEndOf() as $val) {
                if (preg_match($val, $param, $matches) === 1) {

                    if ($matches[1] === '') {
                        $page = $matches[2];
                        $parametres += array (
                            'chunk' => $page,
                            'limit' => $this->page_limit
                        );
                    } elseif ($matches[1] === 'current') {

                        $parametres += array (
                            'current' => $matches[2],
                        );

                        return $this->render($model->getBundleName().':Frontend:item.html.twig', array (
                                'data' => $this->load($parametres),
                                'url' => $url,
                                'navigation' => $other['navigation'],
                                'hierarchy' => $other['hierarchy'],
                                'current' => $this->id_node
                            ));
                    }
                    break;
                }
            }
        } else {
            $parametres += array (
                'limit' => $this->page_limit
            );
        }

        $data = $this->load($parametres);

        $pager = isset($data['parametres']['pager']) ? $data['parametres']['pager'] : false;

        return $this->render($model->getBundleName().':Frontend:index.html.twig', array (
                'data' => $data,
                'pager' => $pager,
                'url' => $url,
                'navigation' => $other['navigation'],
                'hierarchy' => $other['hierarchy'],
                'current' => $this->id_node            
            ));
    }

    public function getAvailableEndOf()
    {
        return array (
            "|^()([0-9]+)$|",
            "|^(current)\\/([0-9]+)$|"
        );
    }

}
