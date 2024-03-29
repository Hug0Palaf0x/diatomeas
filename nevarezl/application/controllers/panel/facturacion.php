<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * @author Ivan
 *
 */
class facturacion extends MY_Controller {
        
    /**
     * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
     * @var unknown_type
     */
    private $excepcion_privilegio = array('facturacion/ajax_get_total_tickets/','facturacion/ajax_get_folio/','facturacion/ajax_agrega_factura/','facturacion/imprimir_pdf/',
                                          'facturacion/ajax_actualiza_digitos/','facturacion/pdf_rm/','facturacion/descargar_rm/',
                                          'facturacion/parchefac/',
                                          'facturacion/ajax_valida_folio/',
                                          'facturacion/get_series/');
    
    public function _remap($method){
        $this->carabiner->css(array(
                array('libs/jquery-ui.css', 'screen'),
                array('libs/ui.notify.css', 'screen'),
                array('libs/jquery.treeview.css', 'screen'),
                array('base.css', 'screen')
        ));
        $this->carabiner->js(array(
                array('libs/jquery.min.js'),
                array('libs/jquery-ui.js'),
                array('libs/jquery.notify.min.js'),
                array('libs/jquery.treeview.js'),
                array('general/alertas.js')
        ));
        
        $this->load->model("empleados_model");
        $this->load->model("aviones_model");
        if($this->empleados_model->checkSession()){
            $this->empleados_model->excepcion_privilegio = $this->excepcion_privilegio;
            $this->info_empleado = $this->empleados_model->getInfoEmpleado($_SESSION['id_empleado'], true);
            if($this->empleados_model->tienePrivilegioDe('', get_class($this).'/'.$method.'/')){
                    $this->{$method}();
            }else
                redirect(base_url('panel/home?msg=1'));
        }else
            redirect(base_url('panel/home'));
    }

    /**
    * parche regenera los archivos de las facturas
    */
    private function parchefac(){
        $this->load->model('facturacion_model');
        $this->facturacion_model->regeneraFacturas();
    }
    
    private function index(){
        $this->carabiner->css(array(
                array('libs/jquery.msgbox.css', 'screen'),
                array('libs/jquery.superbox.css', 'screen'),
                array('general/tables.css', 'screen'),
                array('general/forms.css', 'screen')
        ));
        $this->carabiner->js(array(
                array('libs/jquery.msgbox.min.js'),
                array('libs/jquery.superbox.js'),
                array('general/msgbox.js'),
                array('facturacion/admin.js')
        ));
        $this->load->model('facturacion_model');
        $this->load->library('pagination');
        
        $params['info_empleado'] = $this->info_empleado['info']; //info empleado
        $params['opcmenu_active'] = 'Facturas'; //activa la opcion del menu
        $params['seo'] = array(
                'titulo' => 'Administrar Facturas'
        );

        $this->load->model('empresas_model');
        $params['empresa'] = $this->empresas_model->getInfoEmpresa(1, true);
        $_GET['fid_empresa'] = isset($_GET['fid_empresa'])? $_GET['fid_empresa']: 
                                    (isset($params['empresa']['info'])? $params['empresa']['info']->id_empresa: '');
        
        $params['facturas'] = $this->facturacion_model->getFacturas();
        
        if(isset($_GET['msg']{0}))
                $params['frm_errors'] = $this->showMsgs($_GET['msg']);
        
        $this->load->view('panel/header', $params);
        $this->load->view('panel/general/menu', $params);
        $this->load->view('panel/facturacion/admin', $params);
        $this->load->view('panel/footer');
    }
    
    private function agregar(){
        $this->carabiner->css(array(
                array('libs/jquery.msgbox.css', 'screen'),
                array('libs/jquery.superbox.css', 'screen'),
                array('general/forms.css','screen'),
                array('general/tables.css','screen')
        ));
        
        $this->carabiner->js(array(
                array('libs/jquery.msgbox.min.js'),
                array('libs/jquery.superbox.js'),
                array('libs/jquery.numeric.js'),
                array('general/util.js'),
                array('general/msgbox.js'),
                array('facturacion/frm_addmod.js')
        ));

        $params['info_empleado']        = $this->info_empleado['info'];
        $params['opcmenu_active'] = 'Facturas'; //activa la opcion del menu
        $params['seo']  = array('titulo' => 'Facturar');

        $this->load->library('cfd');
        $this->load->model('facturacion_model');
        
        $query = $this->db->query("SELECT id_serie_folio, (COALESCE(leyenda,'') || '-' || serie) as serie FROM facturacion_series_folios");
        $params['series'] = $query->result();
                        
        // $params['no_certificado'] = $this->cfd->obtenNoCertificado();
        
        $this->load->model('empresas_model');
        $params['empresa'] = $this->empresas_model->getInfoEmpresa(1, true);
        
        if(isset($_GET['msg']{0}))
        	$params['frm_errors'] = $this->showMsgs($_GET['msg']);

        $this->load->view('panel/header',$params);
        $this->load->view('panel/general/menu',$params);
        $this->load->view('panel/facturacion/agregar',$params);
        $this->load->view('panel/footer',$params);
    }
    
    private function ver(){
        if(isset($_GET['id']{0})){
            $this->carabiner->css(array(
                    array('general/forms.css', 'screen'),
                    array('general/tables.css', 'screen')
            ));
            $this->carabiner->js(array(array('facturacion/frm_ver.js')));
            
            $params['info_empleado'] = $this->info_empleado['info']; //info empleado
            $params['seo'] = array('titulo' => 'Ver Factura');
            $params['opcmenu_active'] = 'Facturas'; //activa la opcion del menu
                    
            $this->load->model('facturacion_model');
            $params['factura'] = $this->facturacion_model->getDataFactura($_GET['id']);
            
            $params['factura']['leyenda'] = $this->db->select("leyenda")->from("facturacion_series_folios")->where("serie",$params['factura']['serie'])->get()->row()->leyenda;
            $params['factura']['forma_pago_val'] = (strpos($params['factura']['forma_pago'],'Pago')!==false)?'0':'1';
            $this->load->view('panel/header', $params);
            $this->load->view('panel/general/menu', $params);
            $this->load->view('panel/facturacion/ver',$params);
            $this->load->view('panel/footer');
        }else redirect(base_url('panel/facturacion/?'.String::getVarsLink().'&msg=1'));
    }
    
    private function cancelar(){
        if(isset($_GET['id']{0})){
            $this->load->model('facturacion_model');
            $res = $this->facturacion_model->cancelFactura($_GET['id']);
            if($res[0]) redirect(base_url('panel/facturacion/?'.String::getVarsLink(array('id','msg')).'&msg=5'));
        }else redirect(base_url('panel/facturacion/?'.String::getVarsLink(array('msg')).'&msg=1'));
    }
    
    private function pagar(){
      if(isset($_GET['id']{0})){
        $this->carabiner->css(array(
            array('general/forms.css', 'screen'),
            array('general/tables.css', 'screen'),
        ));
        $this->carabiner->js(array(
            array('facturacion/pago_factura.js')
        ));
        $this->load->model('facturacion_model');
        $this->configAddPago();
        if($this->form_validation->run() == FALSE){
          $params['frm_errors']= $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
        }
        else{
          if (isset($_GET['tipo'])){
            $res = $this->facturacion_model->abonar_factura(false, $_GET['id'], $_POST['fabono']);
            $msg = 10;
          }
          else{
            $res = $this->facturacion_model->abonar_factura(true);
            $msg = 12;
          }
          
          if($res[0]){
            $params['frm_errors'] = $this->showMsgs($msg);
            $params['load'] = true;
          }else $params['frm_errors']= $this->showMsgs(2, $res['msg']);
        }
        
        $res_q= $this->db->select('serie,folio')->from('facturacion')->where('id_factura',$_GET['id'])->get()->result();
        
        $params['serie'] = $res_q[0]->serie;
        $params['folio'] = $res_q[0]->folio;
        $res = $this->facturacion_model->get_info_abonos();
        $params['total'] = $res;

        $params['seo']['titulo'] = 'Pagar Factura';
        if (isset($_GET['tipo'])){
          $params['seo']['titulo'] = 'Abonar Factura';
        }

        $this->load->view('panel/facturacion/pago_factura',$params);
      }else redirect(base_url('panel/facturacion/?'.String::getVarsLink(array('msg')).'&msg=1'));
    }

    public function eliminar_abono(){
      if (isset($_GET['ida']{0}))
      {
        $this->load->model('facturacion_model');
        $res = $this->facturacion_model->eliminar_abono();
        if ($res){
          redirect(base_url('panel/cuentas_cobrar/detalle/?'.String::getVarsLink(array('msg','ida')).'&msg=3'));
        }
      }
      else redirect(base_url('panel/cuentas_cobrar/detalle/?'.String::getVarsLink(array('msg','ida')).'&msg=1'));
    }
    
    private function reporte_mensual() {            
    	$this->carabiner->css(array(
    			array('general/forms.css', 'screen'),
    			array('general/tables.css', 'screen'),
    	));
    	
    	$params['info_empleado'] = $this->info_empleado['info']; //info empleado
        $params['seo'] = array('titulo' => 'Ver Factura');
        $params['opcmenu_active'] = 'Facturas'; //activa la opcion del menu
        
        $this->load->library('form_validation');
        $this->load->library('cfd');
        $this->load->model('facturacion_model');
        
        if(isset($_POST['freporte'])) {
        	$res_gen = $this->cfd->generaReporte($_POST['fano'],$_POST['fmes'],$_POST['str_facturas']);
        	if($res_gen['tipo']==0)
        		$params['frm_errors'] = $this->showMsgs(9);
        }
        
        $rules = array(array(
        				'field' => 'fano',
        				'label' => 'Año', 
        				'rules' => 'required|max_length[4]|callback_isValidYear'));
        
        $this->form_validation->set_rules($rules);
        $params['status'] = 'STATUS: SIN RESULTADOS';
        
         if($this->form_validation->run() == FALSE){
         	$params['frm_errors']= $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
         }
         else{ 	
         	$existe_r = $this->cfd->existeReporte($_POST['fano'],$_POST['fmes']);
         	$res = $this->facturacion_model->getFacturasReporteMensual();
         	
         	if($existe_r || $res!=''){
         		$params['s_generar'] = true;
         		$params['status'] = ($existe_r)?'STATUS: GENERADO':'STATUS: PENDIENTE';
         		
         		if($existe_r){
         			$params['s_descargar'] = true;
         		}
         	}
         	$params['cadena'] = $res;
         }
         
        $params['seo']['titulo'] = 'Reporte Mensual';
        if(isset($_GET['msg']{0}))
        	$params['frm_errors'] = $this->showMsgs($_GET['msg']);
        
        $this->load->view('panel/header',$params);
        $this->load->view('panel/general/menu', $params);
        $this->load->view('panel/facturacion/reporte_mensual',$params);
        $this->load->view('panel/footer');
    }
    
    private function descargar_rm(){
    	$this->load->library('cfd');
    	$this->cfd->descargaReporte($_GET['fano'],$_GET['fmes']);
    }
    
    private function pdf_rm() {
    	$this->load->model('facturacion_model');
    	$this->facturacion_model->getPdfReporteMensual();
    }
    
    public function configAddPago(){
      $this->load->library('form_validation');
      $rules = array(
                  array('field' => 'ffecha',
                        'label' => 'Fecha',
                        'rules' => 'required|max_length[10]|callback_isValidDate'),
                  array('field' => 'fconcepto',
                        'label' => 'Concepto',
                        'rules' => 'required|max_length[200]')
      );

      if (isset($_GET['tipo']))
        $rules[] = array('field' => 'fabono',
                        'label' => 'Total a Abonar',
                        'rules' => 'required|callback_verifica_abono');

      $this->form_validation->set_rules($rules);
    }

    private function ajax_get_folio(){
        if(isset($_POST['id'])){
            if($_POST['id']!=''){
                $this->load->model('facturacion_model');
                $result = $this->facturacion_model->ajax_get_folio($_POST['id']);
                echo json_encode($result);
            }else echo json_encode(array(false,'msg'=>'Seleccione una Serie Valida'));
        }else echo json_encode(array(false,'msg'=>'El campo ID es requerido'));
    }

    /**
   * obtiene el folio siguiente de la serie seleccionada
   */
    public function get_series(){
        if(isset($_GET['ide']))
        {
          $this->load->model('facturacion_model');
          $res = $this->facturacion_model->get_series_empresa($_GET['ide']);

          $param =  $this->showMsgs(2, $res[1]);
          $param['data'] = $res[0];
          echo json_encode($param);
        }
    }

    private function ajax_valida_folio(){
        if (isset($_POST['serie']) && isset($_POST['folio'])) 
        {
            $e = 0;
            if ($_POST['serie'] != '' && $_POST['folio'] != '') 
            {
                $res = $this->db->query("SELECT COUNT(id_factura) as t
                                  FROM facturacion
                                  WHERE serie='".$_POST['serie']."' AND 
                                        folio=".$_POST['folio']."");

                if ($res->row()->t > 0)
                    $e = 1;
            }
            echo $e;
        }
    }
    
    private function ajax_get_total_tickets(){
        $this->load->model('facturacion_model');
        $params = $this->facturacion_model->ajax_get_total_tickets();
        echo json_encode($params);
    }
    
    private function ajax_agrega_factura(){
        $this->load->library('form_validation');
        
        $rules = array(
                array('field' => 'fid_empresa',
                        'label' => 'Empresa',
                        'rules' => 'required|max_length[25]'),
                array('field' => 'hcliente',
                        'label' => 'Cliente',
                        'rules' => 'required|max_length[25]'),
                array('field' => 'dcliente',
                        'label' => 'Cliente',
                        'rules' => 'max_length[130]'),
                array('field' => 'frfc',
                        'label' => 'RFC',
                        'rules' => 'max_length[13]'),
                array('field' => 'fcalle',
                        'label' => 'Calle',
                        'rules' => 'max_length[60]'),
                array('field' => 'fno_exterior',
                        'label' => 'No. Ext',
                        'rules' => 'max_length[7]'),
                array('field' => 'fno_interior',
                        'label' => 'No. Int',
                        'rules' => 'max_length[7]'),
                array('field'   => 'fcolonia',
                                'label'         => 'Colonia',
                                'rules'         => 'max_length[60]'),
                array('field'   => 'flocalidad',
                                'label'         => 'Localidad',
                                'rules'         => 'max_length[45]'),
                array('field'   => 'fmunicipio',
                                'label'         => 'Municipio',
                                'rules'         => 'max_length[45]'),
                array('field'   => 'festado',
                                'label'         => 'Estado',
                                'rules'         => 'max_length[45]'),
                array('field'   => 'fcp',
                                'label'         => 'CP',
                                'rules'         => 'max_length[10]'),
                array('field'   => 'fpais',
                                'label'         => 'País',
                                'rules'         => 'max_length[60]'),                           
                array('field'   => 'fplazo_credito',
                                'label'         => 'Plazo de Crédito',
                                'rules'         => 'required|is_natural'),
                array('field'   => 'dfecha',
                                'label'         => 'Fecha',
                                'rules'         => 'required'),
                array('field'   => 'dcondicion_pago',
                                'label'         => 'Condicion de Pago',
                                'rules'         => 'required'),
                array('field'   => 'dleyendaserie',
                                'label'         => 'Leyenda-Serie',
                                'rules'         => 'required'),
                array('field'   => 'dserie',
                                'label'         => 'Serie',
                                'rules'         => 'max_length[30]'),
                array('field'   => 'dimg_cbb',
                                'label'         => 'Codigo QR',
                                'rules'         => 'required|max_length[100]'),
                array('field'   => 'dfolio',
                                'label'         => 'Folio',
                                'rules'         => 'required|is_natural|callback_seriefolio_check'),
                array('field'   => 'dano_aprobacion',
                                'label'         => 'Año de Aprobación',
                                'rules'         => 'required|max_length[12]|callback_isValidDate'),
                array('field'   => 'dno_aprobacion',
                                'label'         => 'No. de Aprobación',
                                'rules'         => 'required|is_natural'),
                // array('field'   => 'dno_certificado',
                //                 'label'         => 'No. de Certificado',
                //                 'rules'         => 'required|max_length[100]'),
                array('field'   => 'dtipo_comprobante',
                                'label'         => 'Tipo de Comprobante',
                                'rules'         => 'required|max_length[10]'),
                array('field'   => 'dforma_pago',
                                'label'         => 'Forma de Pago',
                                'rules'         => 'required'),
                array('field'   => 'dmetodo_pago',
                                'label'         => 'Metodo de Pago',
                                'rules'         => 'required'),
                array('field'   => 'subtotal',
                                'label'         => 'Subtotal',
                                'rules'         => 'required'),
                array('field'   => 'iva',
                                'label'         => 'Iva',
                                'rules'         => 'required'),
                array('field'   => 'total_isr',
                                'label'         => 'ISR',
                                'rules'         => 'required'),
                array('field'   => 'total',
                                'label'         => 'Total',
                                'rules'         => 'required'),
                array('field'   => 'dttotal_letra',
                                'label'         => 'Importe con Letra',
                                'rules'         => 'max_length[250]'),
                array('field'   => 'fobservaciones',
                                'label'         => 'Observaciones',
                                'rules'         => 'max_length[850]'),
                array('field'   => 'tickets',
                                'label'         => 'Tickets',
                                'rules'         => 'required')
        );
        
        if(isset($_POST['dforma_pago']))
                if($_POST['dforma_pago']==1)
                        $rules[] = array('field' => 'dforma_pago_parcialidad',
                                        'label' => 'Formas de Pago',
                                        'rules' => 'required|max_length[80]');

        if(isset($_POST['dmetodo_pago']))
                if($_POST['dmetodo_pago']!='efectivo' && $_POST['dmetodo_pago']!='')
                        $rules[] = array('field' => 'dmetodo_pago_digitos',
                                        'label' => 'Ultimos 4 digitos',
                                        'rules' => 'max_length[20]');
        
        $this->form_validation->set_rules($rules);
        if($this->form_validation->run() == FALSE)
        {
            $params['msg']  = $this->showMsgs(2,preg_replace("[\n|\r|\n\r]", '', validation_errors()));
        }
        else
        {                       
            $this->load->model('facturacion_model');
            $params = $this->facturacion_model->addFactura();
            if($params[0]){
                $params['msg'] = $this->showMsgs(4);
            }
        }
        echo json_encode($params);
    }

    /**
   * Form_validation: Valida si el usuario ya esta usado por alguien mas
   * @param unknown_type $str
   */
    public function seriefolio_check($str){
        if($str != ''){
          $sql = $ms = '';

          $res = $this->db->select('Count(id_factura) AS num')
            ->from('facturacion')
            ->where("serie = '".$this->input->post('dserie')."' AND folio = '".$str."' AND id_empresa = '". $this->input->post('fid_empresa')."'")
          ->get();
          $data = $res->row();
          if($data->num > 0){
            $this->form_validation->set_message('seriefolio_check', 'El serie y folio ya esta utilizado por otra Factura.');
            return false;
          }
        }
        return true;
    }
    
    private function ajax_actualiza_digitos(){
        $this->load->model('facturacion_model');
        $result = $this->facturacion_model->ajax_actualiza_digitos();
        if($result[0])
            $params['msg'] = $this->showMsgs(8);
        echo json_encode($params);
    }
    
    private function imprimir_pdf(){
        if(isset($_GET['id']{0}))
        {
            $this->load->model('facturacion_model');
            $data = $this->facturacion_model->getDataFactura($_GET['id']);
            $this->load->library('cfd');
            $this->cfd->generarPDFQR($data,array('I'));
        }
    }
    
    private function xml(){
        if(isset($_GET['id']{0})){
            $this->load->library('cfd');
            $this->load->model('facturacion_model');
            $data = $this->facturacion_model->getDataFactura($_GET['id'],true);
            $this->cfd->descargarXML($data);
        }else redirect(base_url('panel/facturacion?'.String::getVarsLink(array('msg')).'&msg=1'));
    }
    
    private function index_series_folios(){
        $this->carabiner->css(array(
                array('general/forms.css','screen'),
                array('general/tables.css','screen')
        ));

        $this->load->library('pagination');
        $this->load->model('facturacion_model');

        $params['info_empleado']        = $this->info_empleado['info'];
        $params['opcmenu_active']       = 'Facturas'; //activa la opcion del menu
        $params['seo']                          = array('titulo' => 'Administrar Series y Folios');

        $params['datos_s'] = $this->facturacion_model->getSeriesFolios();

        $this->load->view('panel/header',$params);
        $this->load->view('panel/general/menu',$params);
        $this->load->view('panel/facturacion/series_folios/admin',$params);
        $this->load->view('panel/footer',$params);
    }
    
    private function agregar_serie_folio(){
        $this->carabiner->css(array(
                        array('general/forms.css','screen'),
                        array('general/tables.css','screen')
        ));
        $this->carabiner->js(array(
            array('facturacion/series_folios/frm_addmod.js')
        ));
                        
        $params['info_empleado']        = $this->info_empleado['info'];
        $params['opcmenu_active'] = 'Facturas'; //activa la opcion del menu
        $params['seo']  = array('titulo' => 'Agregar Series y Folios');
        
        $this->load->model('facturacion_model');
        $this->configAddSerieFolio();
        if($this->form_validation->run() == FALSE)
        {
            $params['frm_errors']   = $this->showMsgs(2,preg_replace("[\n|\r|\n\r]", '', validation_errors()));
        }
        else
        {
            $model_resp     = $this->facturacion_model->addSerieFolio();
            if($model_resp[0])
                redirect(base_url('panel/facturacion/agregar_serie_folio/?'.String::getVarsLink(array('msg')).'&msg=6'));
            else
                $params['frm_errors'] = $this->showMsgs(2,$model_resp[1]);      
        }

        if(isset($_GET['msg']{0}))
            $params['frm_errors'] = $this->showMsgs($_GET['msg']);
        
        $this->load->view('panel/header',$params);
        $this->load->view('panel/general/menu',$params);
        $this->load->view('panel/facturacion/series_folios/agregar',$params);
        $this->load->view('panel/footer',$params);
    }
    
    private function modificar_serie_folio(){       
        if(isset($_GET['id']{0})){
            $this->carabiner->css(array(
                            array('general/forms.css','screen'),
                            array('general/tables.css','screen')
            ));
            $this->carabiner->js(array(
                array('facturacion/series_folios/frm_addmod.js')
            ));
            
            $this->load->model('facturacion_model');
            $this->configAddSerieFolio('edit');
            
            if($this->form_validation->run() == FALSE)
            {
                $params['frm_errors']   = $this->showMsgs(2,preg_replace("[\n|\r|\n\r]", '', validation_errors()));
            }
            else
            {
                $model_resp     = $this->facturacion_model->editSerieFolio($_GET['id']);
                if($model_resp[0])
                        $params['frm_errors']   = $this->showMsgs(3);
            }
            
            $params['info_empleado']        = $this->info_empleado['info'];
            $params['opcmenu_active']       = 'Facturas'; //activa la opcion del menu
            $params['seo']['titulo']        = 'Modificar Serie y Folio';
            
            $params['serie_info']   = $this->facturacion_model->getInfoSerieFolio($_GET['id']);
            
            if(isset($_GET['msg']{0}))
                $params['frm_errors'] = $this->showMsgs($_GET['msg']);
        
            $this->load->view('panel/header',$params);
            $this->load->view('panel/general/menu',$params);
            $this->load->view('panel/facturacion/series_folios/modificar',$params);
            $this->load->view('panel/footer',$params);
        }
        else
            redirect(base_url('panel/facturacion/index_serie_folios/').String::getVarsLink(array('msg')).'&msg=1');
    }
    
    private function configAddSerieFolio($tipo='add'){
        $this->load->library('form_validation');

        $rules = array(
            array('field' => 'fid_empresa',
                'label' => 'Empresa',
                'rules' => 'required|max_lenght[25]'),
            array('field' => 'fempresa',
                'label' => 'Empresa',
                'rules' => 'min_length[1]'),

                array('field'   => 'fno_aprobacion',
                                'label' => 'No Aprobación',
                                'rules' => 'required|numeric'),
                array('field'   => 'ffolio_inicio',
                                'label' => 'Folio Inicio',
                                'rules' => 'required|is_natural'),
                array('field'   => 'ffolio_fin',
                                'label' => 'Folio Fin',
                                'rules' => 'required|is_natural'),
                array('field'   => 'fano_aprobacion',
                                'label' => 'Año Aprobación',
                                'rules' => 'required|max_lenght[10]|callback_isValidDate'),
                array('field'   => 'fleyenda',
                                'label' => 'Leyenda',
                                'rules' => ''),
                array('field'   => 'fleyenda1',
                                'label' => 'Leyenda 1',
                                'rules' => 'required'),
                array('field'   => 'fleyenda2',
                                'label' => 'Leyenda 2',
                                'rules' => 'required')
                        );
        
        if($tipo=='add'){
            if(isset($_FILES['durl_img']))
                    if($_FILES['durl_img']['name']!='')
                            $_POST['durl_img'] = 'ok';

            $rules[] = array('field' => 'fserie',
                            'label' => 'Serie',
                            'rules' => 'max_lenght[30]|callback_isValidSerie[add]');
            $rules[] = array('field' => 'durl_img',
                            'label' => 'Imagen',
                            'rules' => '');
        }
        
        if($tipo=='edit'){
            $rules[] = array('field'        => 'fserie',
                            'label' => 'Serie',
                            'rules' => 'max_lenght[30]|callback_isValidSerie[edit]');
        }
        
        $this->form_validation->set_rules($rules);
    }
    
    /**
     * Form_validation: Valida su una fecha esta en formato correcto
     */
    public function isValidDate($str){
        if($str != ''){
            if(String::isValidDate($str) == false){
                $this->form_validation->set_message('isValidDate', 'El campo %s no es una fecha valida');
                return false;
            }
        }
        return true;
    }
    
    /**
     * Form_validation: Valida si un año esta en formato correcto
     */
    public function isValidYear($str){
        if($str != ''){
            $year = intval($str);
            if( $year<1000 || $year>2100){
                $this->form_validation->set_message('isValidYear', 'El campo %s no es un año valido');
                return false;
            }
        }
        return true;
    }
    
    
    /**
     * Form_validation: Valida si la Serie ya existe
     */
    public function isValidSerie($str, $tipo){
        $str = ($str=='') ? '' : $str;

        if($tipo=='add'){
            if($this->facturacion_model->exist('facturacion_series_folios',array('serie' =>strtoupper($str)))){
                $this->form_validation->set_message('isValidSerie', 'El campo %s ya existe');
                return false;
            }
            return true;
        }
        else{
            $row = $this->facturacion_model->exist('facturacion_series_folios',array('serie' =>strtoupper($str)),true);
            
            if($row!=FALSE){
                if($row->id_serie_folio == $_GET['id'])
                    return true;
                else{
                    $this->form_validation->set_message('isValidSerie', 'El campo %s ya existe');
                    return false;
                }
            }return true;
        }         
    }

    public function verifica_abono($str) {
      // $res = $this->nomina_model->get_info_abonos();
      $res = $this->facturacion_model->get_info_abonos();
      $abono = floatval($str);
      if($abono > $res->restante){
        $this->form_validation->set_message('verifica_abono', 'El Abono que ingreso no puede ser mayor al Saldo');
        return false;
      }
      if($abono == 0){
        $this->form_validation->set_message('verifica_abono', 'El Abono que ingreso no puede ser de Cero (0)');
        return false;
      }
    }
    
    /**
     * Muestra mensajes cuando se realiza alguna accion
     * @param unknown_type $tipo
     * @param unknown_type $msg
     * @param unknown_type $title
     */
    private function showMsgs($tipo, $msg='', $title='Facturacion!'){
        switch($tipo){
            case 1:
                $txt = 'El campo ID es requerido.';
                $icono = 'error';
                break;
            case 2: //Cuendo se valida con form_validation
                $txt = $msg;
                $icono = 'error';
                break;
            case 3:
                $txt = 'La Factura se modificó correctamente.';
                $icono = 'ok';
                break;
            case 4:
                $txt = 'La Factura se agrego correctamente.';
                $icono = 'ok';
                break;
            case 5:
                $txt = 'La Factura se cancelo correctamente.';
                $icono = 'ok';
                break;
            case 6:
                $txt = 'La Serie y Folio se agregaron correctamente.';
                $icono = 'ok';
                break;
            case 7:
                $txt = 'La Serie y Folio se modifico correctamente.';
                $icono = 'ok';
                break;
            case 12:
                $txt = 'La Factura se pagó correctamente.';
                $icono = 'ok';
                break;
            case 8:
                $txt = 'La Factura y los archivos fueron actualizados correctamente.';
                $icono = 'ok';
                break;
             case 9:
                $txt = 'El Reporte se genero correctamente';
                $icono = 'ok';
                break;
             case 10:
                $txt = 'El abono se agrego correctamente';
                $icono = 'ok';
                break;
             case 11:
                $txt = 'El abono se elimino correctamente';
                $icono = 'ok';
                break;
        }
    
        return array(
                    'title' => $title,
                    'msg' => $txt,
                    'ico' => $icono);
    }       
}