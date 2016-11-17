<?php
/**
DEFINE IN A CONFIG FILE 'DBNAME' AND 'SERVER_URL'
*/

require_once 'xmlrpc/lib/xmlrpc.inc';

/* From: http://stackoverflow.com/questions/3835636/php-replace-last-occurence-of-a-string-in-a-string */
function str_lreplace($search, $replace, $subject)
{
    $pos = strrpos($subject, $search);
    if($pos !== false)
    {
        $subject = substr_replace($subject, $replace, $pos, strlen($search));
    }
    return $subject;
}

/* From: http://stackoverflow.com/questions/173400/how-to-check-if-php-array-is-associative-or-sequential */
function isAssoc(array $arr)
{
    if (array() === $arr) return false;
    return array_keys($arr) !== range(0, count($arr) - 1);
}

function _log($msg) {
	
}


class openerp {
    public $user = 'admin';
    public $password = USER_PASS;
    public $dbname = DBNAME;
    public $uid;
    public $client;
    public $server_url = SERVER_URL;
    public $return_type;
	
	protected $is_connected = false;
	protected $is_logged = false;

	public function isConnected() {
		return $this->is_connected;
	}
	
	public function isLogged() {
		return $this->is_logged;
	}
	
    public function connect() {
        $this->client = new xmlrpc_client($this->server_url . "/xmlrpc/common");
        $this->client->setSSLVerifyPeer(0);
		$this->is_connected = true;
    }
    
    public function login($user = null, $password = null) {
		if ($user) {
			$this->user = $user;
		}
		if ($password) {
			$this->password = $password;
		}
		if (!$this->isConnected()) $this->connect();
        $c_msg = new xmlrpcmsg('login');
        $c_msg->addParam(new xmlrpcval($this->dbname, "string"));
        $c_msg->addParam(new xmlrpcval($this->user, "string"));
        $c_msg->addParam(new xmlrpcval($this->password, "string"));
        $c_response = $this->client->send($c_msg);
        $this->uid = $c_response->value()->scalarval();
		$this->is_logged = true;
        return $this->uid;
    }

    public function read($id, $object, $fields = '') {
        if (!is_array($id)) {
            $id = array($id);
        }
        foreach ($id as &$item) {
            if (is_string($item)) $type = 'string';
            else $type = 'int';
            $item = new xmlrpcval($item, $type);
        }
        $type_fields = 'string';
        if ($fields) {
            foreach ($fields as &$field) {
                $field = new xmlrpcval($field, 'string');
            }
            $type_fields = 'array';
        }
        $this->client_obj = new xmlrpc_client($this->server_url . '/xmlrpc/object');
        $this->client_obj->setSSLVerifyPeer(0);
        if ($this->return_type) $this->client_obj->return_type = 'phpvals';
        
        $context = array(
            'lang' => new xmlrpcval('pt_BR', 'string')
        );
        $msg = new xmlrpcmsg('execute');
        $msg->addParam(new xmlrpcval($this->dbname, "string"));
        $msg->addParam(new xmlrpcval($this->uid, "int"));
        $msg->addParam(new xmlrpcval($this->password, "string"));
        $msg->addParam(new xmlrpcval($object, "string"));
        $msg->addParam(new xmlrpcval("read", "string"));
        $msg->addParam(new xmlrpcval($id, "array"));
        $msg->addParam(new xmlrpcval($fields, $type_fields));
        $msg->addParam(new xmlrpcval($context, 'struct'));
        $resp = $this->client_obj->send($msg);
        
        try {
            if ($resp->errno != 0) {
                echo '<h1>Erro na requisição ao OpenERP</h1>';
                echo $resp->errstr;
                exit;
            }
        } catch (Exception $e) {
            var_dump($e);
            exit;
        }
        $value = $resp->value();
        return $value;
    }
    
    public function search($domain, $object) {
        $this->client_obj = new xmlrpc_client($this->server_url . '/xmlrpc/object');
        $this->client_obj->setSSLVerifyPeer(0);
        if ($this->return_type) $this->client_obj->return_type = 'phpvals';
        
        $domain_filter = array();
        foreach ($domain as $item) {
            if (is_array($item[2])) {
                foreach ($item[2] as $key=>&$value) {
                    $value = new xmlrpcval($value, 'string');
                }
                $item[2] = new xmlrpcval($item[2], 'array');
            } else {
                $item[2] = new xmlrpcval($item[2]);
            }
            $domain_filter[] = new xmlrpcval(array(new xmlrpcval($item[0]), new xmlrpcval($item[1]), $item[2]), "array");
        }
        $msg = new xmlrpcmsg('execute');
        $msg->addParam(new xmlrpcval($this->dbname, "string"));
        $msg->addParam(new xmlrpcval($this->uid, "int"));
        $msg->addParam(new xmlrpcval($this->password, "string"));
        $msg->addParam(new xmlrpcval($object, "string"));
        $msg->addParam(new xmlrpcval("search", "string"));
        $msg->addParam(new xmlrpcval($domain_filter, "array"));
        $response = $this->client_obj->send($msg);
        $value = $response->value();
        return $value;
    }
    
    public function searchIds($domain, $object) {
        $result = $this->search($domain, $object);
        $ids = array();
        foreach ($result as $key=>$value) {
            foreach ($value['array'] as $p) {
                $ids[] = $p->me['int'];
            }
        }
        return $ids;
    }
    
	public function custom($object, $function, $params = array()) {
		$this->client_obj = new xmlrpc_client($this->server_url . '/xmlrpc/object');
		$this->client_obj->setSSLVerifyPeer(0);
        if ($this->return_type) $this->client_obj->return_type = 'phpvals';
		
		$context = array(
            'lang' => new xmlrpcval('pt_BR', 'string')
        );

		$params = $this->convert_params_xmlrpcvals($params);
		
        $msg = new xmlrpcmsg('execute');
        $msg->addParam(new xmlrpcval($this->dbname, "string"));
        $msg->addParam(new xmlrpcval($this->uid, "int"));
        $msg->addParam(new xmlrpcval($this->password, "string"));
        $msg->addParam(new xmlrpcval($object, "string"));
        $msg->addParam(new xmlrpcval($function, "string"));
		foreach ($params as $param) {
			$msg->addParam($param);
		}
        $msg->addParam(new xmlrpcval($context, 'struct'));
        $resp = $this->client_obj->send($msg);
		if ($resp->faultCode() == 0)
			return $resp->value()->scalarval();
		// Caso contrário dispara uma exception
		throw new Exception('XML-RPC: Code: ' . $resp->faultCode() . ', Message: ' . $resp->faultString());
	}
	
	public function convert_params_xmlrpcvals($params) {
		$result = array();
		$map = array('boolean'=>'boolean', 'integer'=>'int', 'double'=>'double', 'string'=>'string');
		foreach ($params as $key=>$param) {
			$type = gettype($param);
			if (in_array($type, array_keys($map))) {
				$result[$key] = new xmlrpcval($param, $map[$type]);
			} elseif ($type == 'array') {
				if (isAssoc($param)) {
					// struct
					$result[$key] = new xmlrpcval($this->convert_params_xmlrpcvals($param), 'struct');
				} else {
					// array
					$result[$key] = new xmlrpcval($this->convert_params_xmlrpcvals($param), 'array');
				}
			} else {
				// Invalid value, log
				_log('Invalid value: TYPE: ' . $type . ', CONTENT: ' . var_export($param, true));
			}
		}
		return $result;
	}
    public function move_stock_by_date($product_id, $date) {
        $this->client_obj = new xmlrpc_client($this->server_url . '/xmlrpc/object');
        $this->client_obj->setSSLVerifyPeer(0);
        if ($this->return_type) $this->client_obj->return_type = 'phpvals';
        
        $context = array(
            'lang' => new xmlrpcval('pt_BR', 'string')
        );
        $msg = new xmlrpcmsg('execute');
        $msg->addParam(new xmlrpcval($this->dbname, "string"));
        $msg->addParam(new xmlrpcval($this->uid, "int"));
        $msg->addParam(new xmlrpcval($this->password, "string"));
        $msg->addParam(new xmlrpcval('stock.move', "string"));
        $msg->addParam(new xmlrpcval("stock_by_date", "string"));
        $msg->addParam(new xmlrpcval($product_id, "string"));
        $msg->addParam(new xmlrpcval($date, 'string'));
        $msg->addParam(new xmlrpcval($context, 'struct'));
        $resp = $this->client_obj->send($msg);
        return $resp->value()->scalarval();
    }
    
    public function get_move_out_materia_prima($ids) {
        $domain_filter = array();
        foreach ($ids as $item) {
            $domain_filter[] = new xmlrpcval($item, 'int');
        }
        $ids = $domain_filter;
        $this->client_obj = new xmlrpc_client($this->server_url . '/xmlrpc/object');
        $this->client_obj->setSSLVerifyPeer(0);
        if ($this->return_type) $this->client_obj->return_type = 'phpvals';
        
        $context = array(
            'lang' => new xmlrpcval('pt_BR', 'string')
        );
        $msg = new xmlrpcmsg('execute');
        $msg->addParam(new xmlrpcval($this->dbname, "string"));
        $msg->addParam(new xmlrpcval($this->uid, "int"));
        $msg->addParam(new xmlrpcval($this->password, "string"));
        $msg->addParam(new xmlrpcval('estribos.produtividade.colunas.registro', "string"));
        $msg->addParam(new xmlrpcval("get_move_out_materia_prima", "string"));
        $msg->addParam(new xmlrpcval($ids, "array"));
        $msg->addParam(new xmlrpcval($context, 'struct'));
        $resp = $this->client_obj->send($msg);
        $ret = $resp->value()->scalarval();
        $result = array();
        foreach ($ret as $key=>$value) {
            $result[$key] = $this->get_move_out_values($value->scalarval());
        }
        return $result;
    }
    
    public function get_calc_price_product($ids) {
        $domain_filter = array();
        foreach ($ids as $item) {
            $domain_filter[] = new xmlrpcval($item, 'int');
        }
        $ids = $domain_filter;
        $this->client_obj = new xmlrpc_client($this->server_url . '/xmlrpc/object');
        $this->client_obj->setSSLVerifyPeer(0);
        if ($this->return_type) $this->client_obj->return_type = 'phpvals';
        
        $context = array(
            'lang' => new xmlrpcval('pt_BR', 'string')
        );
        $msg = new xmlrpcmsg('execute');
        $msg->addParam(new xmlrpcval($this->dbname, "string"));
        $msg->addParam(new xmlrpcval($this->uid, "int"));
        $msg->addParam(new xmlrpcval($this->password, "string"));
        $msg->addParam(new xmlrpcval('product.product', "string"));
        $msg->addParam(new xmlrpcval("calc_new_price_regs", "string"));
        $msg->addParam(new xmlrpcval($ids, "array"));
        $msg->addParam(new xmlrpcval($context, 'struct'));
        $resp = $this->client_obj->send($msg);
        $ret = $resp->value()->scalarval();
        
        $result = array();
        foreach ($ret as $key=>$value) {
            $result[$key] = $this->get_calc_price_product_values($value->scalarval());
        }
        return $result;
    }
    
    public function get_move_out_values($values) {
        if ($values) {
            $data = array();
            foreach ($values as $value) {
                $scalarval = $value->scalarval();
                $info = array(
                    'product_id'=>$scalarval[0]->scalarval(),
                    'quantidade'=>$scalarval[1]->scalarval(),
                    'quantidade_total'=>$scalarval[2]->scalarval(),
                    'preco'=>$scalarval[3]->scalarval()
                );
                
                $data[] = $info;
            }
            return $data;
        }
        return $values;
    }
    
    public function get_calc_price_product_values($values) {
        if ($values) {
            $data = array();
            foreach ($values as $value) {
                $scalarval = $value->scalarval();
                $item = array();
                if ($scalarval) {
                    foreach ($scalarval as $k=>$v) {
                        $item[$k] = $v->scalarval();
                    }
                }
                $data[] = $item;
            }
        }
        return $data;
    }
    
    public $field_interact = array();
    public $field_interact_total = array();
    public function resetFields() {
        $this->field_interact = array();
        $this->field_interact_total = array();
    }
    public function field_interact($field) {
        if (isset($this->field_interact[$field])) {
            $this->field_interact[$field]++;
        } else {
            $this->field_interact[$field] = 0;
        }
        return $this->field_interact[$field];
        
    }
    public function fetchFields($fields, $values, $types, $is_array=False, $is_id=false,$default=null) {
        if (!is_array($fields)) $fields = array($field);
        $f = $fields[0];
        if (isset($this->field_interact_total[$f]) && $this->field_interact_total[$f] <= $this->field_interact[$f]) return false;
        $values = array();
        foreach ($fields as $field) {
            $value = $this->getField($field, $values, $types[$field], $is_array, $is_id, $default);
            $values[$field] = $value;
        }
        return $values;
    }
    public function getField($field, $values, $type = 'string', $is_array = False, $is_id = false, $default=null) {
        try {
            $index = 0;
            if (count($values->me['array']) > 1) {
                $index = $this->field_interact($field);
                $this->field_interact_total[$field] = count($values->me['array']);
            }
            
            $value = $values->me['array'][$index]->me['struct'][$field]->me;
            if ($is_array) {
                $result = array();
                foreach ($value['array'] as $item) {
                    if ($is_id === 'name') $type = 'string';
                    $result[] = $item->me[$type];
                }
                if ($is_id === 'name') {
                    return utf8_encode($result[1]);
                } elseif ($is_id) {
                    return utf8_encode($result[0]);
                }
                return $result;
            } else {
                return utf8_encode($value[$type]);
            }
        } catch (Exception $e) {
            return $default;
        }
    }
    public static $self;
    public static function i() {
        if (self::$self == null) {
            self::$self = new openerp();
        }
        return self::$self;
    }
}
function showPDF($html, $css, $size = 'A4') {
    $mpdf=new mPDF('utf-8', $size);
    $mpdf->setFooter('{PAGENO}');
    $mpdf->shrink_tables_to_fit=0;
    $mpdf->WriteHTML($css, 1);
    $mpdf->WriteHTML($html, 2);
    $mpdf->SetDisplayMode('fullwidth');
    //$mpdf->Output('pdfs/'.$hash.'.pdf', 'F');
    $mpdf->Output();
    exit();
}

function printHtml($html, $css, $pass='') {
    echo '
    <!DOCTYPE><html><head><title>Relatorios :: OpenERP</title><meta charset="UTF-8"><style type="text/css">' . $css . '</style></head><body>' . $html . '</body></html>
    ';
}
function formatDate($date_openerp) {
    return date('d/m/Y', strtotime($date_openerp));
}

function formatCurrency($value) {
    return 'R$' . number_format($value, 2, ',', '.');
}

class request {
    public static $instance;
    public static function i() {
        if (self::$instance == null) {
            self::$instance = new request();
        }
        return self::$instance;
    }
    
    public function get($var, $default = null) {
        if (isset($_GET[$var])) {
            return $_GET[$var];
        }
        if (isset($_POST[$var])) {
            return $_POST[$var];
        }
        return $default;
    }
}
class OpenERPOSV {
    public $external_object;
    public $_data = array();
    public $fields = array();
    public $magical_fields = array();
    public $relations = array();
    public $field_interact = array();
    public $field_interact_total = array();
    
    
    public function __construct($object, $id = null, $fields = '') {
		// Check if is connected and logged
		if (!openerp::i()->isConnected() || !openerp::i()->isLogged()) {
			throw new Exception('O usuário não está conectado. Use openerp::i()->login(user, pass) para fazer login');
		}
        $this->external_object = $object;
        if ($id) {
            $this->find($id, $fields);
        }
    }
    
    public function __set($name, $value) {
        $this->_data[$name] = $value;
    }
    
    public function setData($data, $index = 0) {
        if (!is_object($data)) {
            throw new Exception('Resultado de "read()" para o objeto ' . $this->external_object . ' é inválido');
        }
        $vals = $data->scalarval();
        $val = $vals[$index];
        foreach ($val->scalarval() as $field=>$value) {
            if (is_array($value->scalarval())) {
                // Então é uma relação
                if (!in_array($field, $this->relations)) $this->relations[] = $field;
                // Se tiver 2 posições, pode ser uma relação direta. Nesse caso vamos armazenar os dois valores apenas para um recurso especial
                $result = $value->scalarval();
                if (count($result) == 2) {
                    // Verificamos se o valor 2 é do tipo string
                    if (is_string($result[1]->scalarval())) {
                        // Criamos os campos mágicos
                        $m_field_id = $field . '_id';
                        $m_field_name = $field . '_name';
                        $this->magical_fields[] = $m_field_id;
                        $this->magical_fields[] = $m_field_name;
                        $this->_data[$m_field_id] = $result[0]->scalarval();
                        $this->_data[$m_field_name] = utf8_encode($result[1]->scalarval());
                        $this->_data[$field] = $result;
                    } else {
                        // Nesse caso lista normalmente
                        $this->_data[$field] = array();
                        foreach ($result as $item) {
                            $this->_data[$field][] = $item->scalarval();
                        }
                    }
                } else {
                    $this->_data[$field] = array();
                    foreach ($result as $item) {
                        $this->_data[$field][] = $item->scalarval();
                    }
                }
            } else {
                $this->_data[$field] = utf8_encode($value->scalarval());
            }
            if (!in_array($field, $this->fields)) $this->fields[] = $field;
        }
    }
    
    public function __get($name) {
        if (in_array($name, $this->fields)) {
            return $this->_data[$name];
        }
        if (in_array($name, $this->magical_fields)) {
            return $this->_data[$name];
        } else {
            // Nesse caso ainda verificamos se o valor original não é false
            $original_name = str_lreplace('_id', '', $name);
            if (!in_array($original_name, $this->fields)) {
                $original_name = str_lreplace('_name', '', $name);
            }
            if (in_array($original_name, $this->fields)) {
                return '';
            }
        }
        if (!isset($this->_data[$name])) {
            throw new Exception('O campo ' . $name . ' não existe no objeto "' . $this->external_object . '". Ele pode não existir no servidor externo ou não ter sido selecionado');
        }
    }
    
    public function searchIds($domain) {
        $ids = openerp::i()->searchIds($domain, $this->external_object);
        return $ids;
    }
    
    public function find($id, $fields = '') {
        $this->setData(openerp::i()->read($id, $this->external_object, $fields));
    }
    
    public function recs($ids, $fields = '') {
        $data = openerp::i()->read($ids, $this->external_object, $fields);
        $recs = array();
        $records = count($data->scalarval());
        for ($i = 0; $i < $records; $i++) {
            $o = new OpenERPOSV($this->external_object);
            $o->setData($data, $i);
            $recs[] = $o;
        }
        return $recs;
    }
    
    public function search($domain = array(), $fields = '') {
        return $this->recs($this->searchIds($domain), $fields);
    }
}
