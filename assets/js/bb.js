/**
 *  Métodos que tratam do acesso à janela popup dos pagamentos
 */
var retorno;
var mpg_popup;
window.name="loja";
function fabrewin(jan) {

    if(navigator.appName.indexOf("Netscape") != -1) {
       mpg_popup = window.open("", "mpg_popup","toolbar=0,location=0,directories=0,status=1,menubar=0,scrollbars=1,resizable=0,screenX=0,screenY=0,left=0,top=0,width=800,height=600");
     }
    else {
       mpg_popup = window.open("", "mpg_popup","toolbar=0,location=0,directories=0,status=1,menubar=0,scrollbars=1,resizable=1,screenX=0,screenY=0,left=0,top=0,width=800,height=600");
    }
	return true;
}

function OpenSaibaMais(){
	window.open('http://www.visa.com.br/vbv/vbv_saibamais.asp','principal','height=435,width=270,top=0,left=0,resizable=no,status=1')
}
function FormataValor(id,tammax,teclapres) {

	if(window.event) { // Internet Explorer
	    var tecla = teclapres.keyCode;
	} else if(teclapres.which) { // Nestcape / firefox
    	var tecla = teclapres.which;
	}

	vr = document.getElementById(id).value;
	vr = vr.toString().replace( "/", "" );
	vr = vr.toString().replace( "/", "" );
	vr = vr.toString().replace( ",", "" );
	vr = vr.toString().replace( ".", "" );
	vr = vr.toString().replace( ".", "" );
	vr = vr.toString().replace( ".", "" );
	vr = vr.toString().replace( ".", "" );
	tam = vr.length;

	if (tam < tammax && tecla != 8){ tam = vr.length + 1; }
	if (tecla == 8 ){ tam = tam - 1; }
    if ( tecla == 8 || tecla >= 48 && tecla <= 57 || tecla >= 96 && tecla <= 105 ){
		if ( tam <= 2 ){
		    document.getElementById(id).value = vr; }
		if ( (tam > 2) && (tam <= 5) ){
		    document.getElementById(id).value = vr.substr( 0, tam - 2 ) + ',' + vr.substr( tam - 2, tam ); }
		if ( (tam >= 6) && (tam <= 8) ){
		    document.getElementById(id).value = vr.substr( 0, tam - 5 ) + '' + vr.substr( tam - 5, 3 ) + ',' + vr.substr( tam - 2, tam ); }
		if ( (tam >= 9) && (tam <= 11) ){
		    document.getElementById(id).value = vr.substr( 0, tam - 8 ) + '.' + vr.substr( tam - 8, 3 ) + '.' + vr.substr( tam - 5, 3 ) + ',' + vr.substr( tam - 2, tam ); }
		if ( (tam >= 12) && (tam <= 14) ){
		    document.getElementById(id).value = vr.substr( 0, tam - 11 ) + '.' + vr.substr( tam - 11, 3 ) + '.' + vr.substr( tam - 8, 3 ) + '.' + vr.substr( tam - 5, 3 ) + ',' + vr.substr( tam - 2, tam ); }
		if ( (tam >= 15) && (tam <= 17) ){
		    document.getElementById(id).value = vr.substr( 0, tam - 14 ) + '.' + vr.substr( tam - 14, 3 ) + '.' + vr.substr( tam - 11, 3 ) + '.' + vr.substr( tam - 8, 3 ) + '.' + vr.substr( tam - 5, 3 ) + ',' + vr.substr( tam - 2, tam );
	    }
	}
}

var tentativa = 1;
var aviso = 1;

function getBloqueador() {
    var janela = window.open("#","janelaBloq", "width=1, height=1, top=0, left=0, scrollbars=no, status=no, resizable=no, directories=no, location=no, menubar=no, titlebar=no, toolbar=no");
    if (janela == null) {
        if (tentativa == 1) {
            alert("Bloqueador de popup ativado. Clique na barra amarela do seu navegador e marque a opção 'Sempre permitir para este site'.");
            tentativa++;
            return false;
        } else if ((tentativa > 1) && (tentativa <= 3)) {
            alert("Tentativa " + tentativa + " de 3: O bloqueador ainda está ativado.");
            tentativa++;
            return false;
        } else if (tentativa > 3) {
			if (aviso == 1) {
				if (confirm("O bloqueador de popups ainda está ativado, você pode ter dificuldades para acessar o site.\n\nDeseja continuar assim mesmo?")) {
					aviso = 0;
					return true;
                } else {
					aviso = 0;
					return false;
                }
			}
        }
    } else {
		janela.close();
		return true;
    }
}

/**
 *  Métodos de acesso/persistencia na base
 */
var erro = false;
/**
 *  Métodos para cálculo das parcelas
 */
function calcula_parcelas(
	valor,
	valor_maximo_visa,
	valor_maximo_master,
	valor_maximo_diners,
	valor_maximo_elo,
	valor_maximo_amex,
	valor_maximo_discover,
	valor_maximo_jcb,
	valor_maximo_aura) {

	var ident;
	var v = valor.replace(/,/,'.');
	var conteudo;
	var v_parcela;

	// coloca o valor no campo hidden do Master
	$('total_pedido').value = v;

	if (v < valor_maximo_visa || v < valor_maximo_visa) {
		if ($('tipo_electron').checked || $('tipo_maestro').checked){
			mostra_erro(false);
		} else {
			mostra_erro(true);
		}
	} else {
		mostra_erro(false);
	}

	// visa e mastercard
	for (i=1; i < 8; i++) {
		ident = 'p0'+i;
		// para os dados para a visa
		cria_parcelas(ident,v,conteudo,i,valor_maximo_visa,valor,false);

		ident = 'v0'+i;
		// para os dados para o master
		cria_parcelas(ident,v,conteudo,i,valor_maximo_master,valor,true);

		ident = 'd0'+i;
		// para os dados para o diners
		cria_parcelas(ident,v,conteudo,i,valor_maximo_diners,valor,true);
		
		ident = 'e0'+i;
		// para os dados para o elo
		cria_parcelas(ident,v,conteudo,i,valor_maximo_elo,valor,true);
		
		ident = 'a0'+i;
		// para os dados para o amex
		cria_parcelas(ident,v,conteudo,i,valor_maximo_amex,valor,true);
		
		ident = 's0'+i;
		// para os dados para o discover
		cria_parcelas(ident,v,conteudo,i,valor_maximo_discover,valor,true);

		ident = 'j0'+i;
		// para os dados para o discover
		cria_parcelas(ident,v,conteudo,i,valor_maximo_jcb,valor,true);

		ident = 'a0'+i;
		// para os dados para o discover
		cria_parcelas(ident,v,conteudo,i,valor_maximo_aura,valor,true);		
		
	}

}

// método generico para criação das parcelas do Mastercard, Visa, Elo, diners, Discover e Amex
function cria_parcelas(ident,v,conteudo,i,valor_maximo,valor,master) {

	span = $(ident);
	if (typeof(span) != undefined && span != null) {
		v_parcela	= v/i;
		conteudo = i+' x de R$ '+arredondamento(Math.round(v_parcela*100)/100,2);
		// mesmo que o valor seja inferior à parcela mínima, mostra a parcela número 1
		if (v_parcela >= valor_maximo || i == 1) {
			span.parentNode.style.display='block';
			span.innerHTML = conteudo;
		} else {
			span.parentNode.style.display='none';
		}
	}
}

// função que arrenda números x com n casas decimais
function arredondamento (x, n){
	if (n < 0 || n > 10) return x;
	var pow10 = Math.pow (10, n);
	var y = x * pow10;
	return (Math.round (y)/pow10).toFixed(2);
}

function show_parcelas(item) {

	var id		= '';
	var debito  = new Array('visa_electron','maestro');
	var credito = new Array('visa','master','diners','elo','amex','discover','jcb','aura');

	// para cada, esconde os outros e mostra somente a div de pagamentos da bandeira solicitada
	// verifica as formas de pagamento crédito para esconder o erro
	for (var c in credito) {
		id = 'div_'+credito[c];
		if ($(id) != null) {
			if (this.erro) {
				mostra_erro(true);
			} else {
				mostra_erro(false);
			}
			mostra_div(id,item,credito[c]);
		}
	}

	// verifica as formas de pagamento débito para esconder o erro
	for (var d in debito) {
		id = 'div_'+debito[d];
		if ($(id) != null) {
			if (status_erro()=='block'){
				mostra_erro(false);
			}
			mostra_div(id,item,debito[d]);
		}
	}
}

function mostra_div(id,item,valor) {
	if (item == valor) {
		jQuery('#'+id).show();
		//$(id).style.display='block';
	} else {
		jQuery('#'+id).hide();
		//$(id).style.display='none';
	}
}

function mostra_erro(erro) {
	if (erro) {
		//$('div_erro').style.display = 'block';
		jQuery('#div_erro').show();
	} else {
		//$('div_erro').style.display = 'none';
		jQuery('#div_erro').hide();
	}
}

function status_erro() {	
	return jQuery('#div_erro').css('display');
}

// Método que marca o campo radio manualmente ( para o ie )
function marcar_radio(id) {
	jQuery('#'+id).attr('checked','checked');
}

function validaForm() {
	jQuery('#div_erro').show();
	jQuery('#div_erro').addClass('error');

	if (jQuery('input[name="tipo_pgto"]:checked').val() == undefined) {
	   	jQuery('#div_erro').text('Escolha um Cartão de Crédito');
	   	return false;
	}
	
	if (jQuery('input[name="nome_impresso"]').length > 0 && jQuery('input[name="nome_impresso"]').val() == '') {
	   	jQuery('#div_erro').text('Digite o nome impresso no Cartão de Crédito');
	   	return false;
	}
	
	if (jQuery('input[name="parcelamento"]:checked').val() == undefined) {
	   	jQuery('#div_erro').text('Escolha uma Forma de Parcelamento');
	   	return false;
	}
	jQuery('#div_erro').hide();
	return true;
}


function submeter_redecard(formulario) {	
    var id = 'form#'+formulario.id;
	
    var cartao_selecionado 	= jQuery(id+' input[name=tipo_pgto]:checked').val();
	var parcela_selecionada = jQuery(id+' input[name=parcelamento]:visible:checked').val();
	var virtuemart_order_id = jQuery(id+' input[name=virtuemart_order_id]').val();
	var order_id 			= jQuery(id+' input[name=order_id]').val();

	jQuery('#div_erro').show();
	jQuery('#div_erro').addClass('error');

	if (cartao_selecionado == '') {
		jQuery('#div_erro_conteudo').text('Selecione um Cartão de Crédito');
		msgPop();
		return erro_cartao();
	}
	if (parcela_selecionada == '') {
		jQuery('#div_erro_conteudo').text('Selecione um parcelamento do Cartão de Crédito');
		msgPop();
		return erro_cartao();
	}
	erro = false;

	var cartoes = new Array();
	cartoes['diners'] 	= 'Diners';
	cartoes['visa'] 	= 'Visa';
	cartoes['master'] 	= 'Mastercard';

	// dados para salvar
	var dados = 'order_id='+order_id+
				'&virtuemart_order_id='+virtuemart_order_id+				
				'&cartao_selecionado='+cartao_selecionado+
				'&parcela_selecionada='+parcela_selecionada;
				'&redecard=1';

	jQuery.ajax({
		type: "POST",
		url: url_salva_redecard,
		data: dados,
		success: function(retorno){
			url_pagamento_redecard = url_pagamento_redecard+ '&order_id='+order_id;
			// redireciona para a Redecard
			if (use_fancybox) {
				jQuery.fancybox({
			        'type': 'iframe', 
			        'width' : 750,
			        'height' : 500,
			        'autoDimensions' : false,
			        'autoScale' : false,
			        'href' : url_pagamento_redecard
			    });
			} else {
				SqueezeBox.initialize();
				SqueezeBox.open(url_pagamento_redecard, {handler: 'iframe', size: {x:750, y:500}});				
			}
		},
		error: function(){
			alert('Erro ao efetuar o pagamento, tente novamente.');
		}
	});
	
	return false;
}
