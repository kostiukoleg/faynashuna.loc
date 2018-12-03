var calc_res_ok = false;
var old_constr_type_id = -1;

function ShowTestData()
{
	// стоимость вверхней части
	return;
	$("#wnd_vert_size input").val("500");
	$("#wnd_1 input").val("500");
	$("#wnd_2 input").val("500");
	$("#wnd_3 input").val("500");
	$("#balcony input").val("500");
	$("#balcony_vert_size input").val("2000");
}

function OpenTypeIcoCss(el, border_color)
{
	var t = parseInt(el.css("top")); var l = parseInt(el.css("left"));
	
	if (border_color != "") el.css({ border: "1px solid "+border_color, borderTop: "0px", borderLeft: "0px", top: t-1, left: l-1 }); 
	else el.css({ border: "0px", top: t+1, left: l+1 }); 
}
function InitData()
{
	// очистка всех input'ов
	$(".windows_block input").val("");
	
	// скрываем все типы открываний и москитные сетки
	$(".window .left_open, .window .right_open, .window .top_open, .window .moskit").hide();
	
	// скрываем все окна, двери и размеры
	$("#wnd_vert_size, .wnd, .balcony, #balcony_vert_size, .open_type").hide();
	
	// выставляем переключатели типа открывания
	$(".open_type .t").each(function () {
		if (($(this).attr("id") == "t1") && ($(this).attr("marked") != 1))
		{
			$(this).css({ border: "1px solid #999", borderTop: "0px", borderLeft: "0px", top: parseInt($(this).css("top"))-1, left: parseInt($(this).css("left"))-1 });
			$(this).attr("marked", 1);
		}
		else if (($(this).attr("id") != "t1") && ($(this).attr("marked") == 1))
		{
			$(this).css({ border: "0px", top: parseInt($(this).css("top"))+1, left: parseInt($(this).css("left"))+1 }); 
			$(this).attr("marked", 0);
		} 
    });
	
	// отключаем везде москитную сетку
	$(".open_type .moskit").removeClass("m_sel"); $(".open_type .moskit").addClass("m_inactive"); $(".open_type .moskit").attr("moskit_on", 0);
	
	$(".open_type").hide();
	
	$(".error_block").html("");
	
	calc_res_ok = false;
}
function ShowLightVerDefaults(type_id)
{
	switch (type_id)
	{
		case "1": $("#wnd_1 .left_open").show(); break;
		case "2": $("#wnd_2 .left_open").show(); break;
		case "3": $("#wnd_2 .left_open").show(); break;
		case "5": $("#wnd_1 .right_open").show(); break;
	}
}
function ShowConstr(type_id)
{
	InitData();
	
	if (type_id == "6")
	{
		$(".balcony .size_div").css({backgroundPosition: "0px 2px"});
		$(".open_type_block").hide();
	}
	else
	{
		$(".open_type_block").show();
		$("#wnd_vert_size, #wnd_1, #open_type_1").show();
		$(".balcony .size_div").css({backgroundPosition: "-1px 2px"});
	}	
	
	if (parseInt(type_id) <= 3) $("#spacer_div").width(0); else $("#spacer_div").width(134);
	
	switch (type_id)
	{
		case "2": $("#wnd_2, #open_type_2").show(); break;
		case "3": $("#wnd_2, #wnd_3, #open_type_2, #open_type_3").show(); break;
		case "4": $("#balcony, #balcony_vert_size").show(); break;
		case "5": $("#wnd_2, #balcony, #balcony_vert_size, #open_type_2").show(); break;
		case "6": $("#balcony, #balcony_vert_size").show(); break;
	}
	
	// включаем типы открываний по-умолчанию для облегченной версии
	if (light_ver) ShowLightVerDefaults(type_id);
	
	ShowTestData();
}
function ValidateData()
{
	var wnd_h = $("#wnd_vert_size input");
	var wnd_1_w = $("#wnd_1 input");
	var wnd_2_w = $("#wnd_2 input");
	var wnd_3_w = $("#wnd_3 input");
	var balcony_w = $("#balcony input");
	var balcony_h = $("#balcony_vert_size input");
	
	var err_msg = "";

	if ((wnd_h.is(":visible")) && ((wnd_h.val() < 500) || (wnd_h.val() > 2300))) err_msg += "<div>Высота окон указана не верно.</div>";
	if ((wnd_1_w.is(":visible")) && ((wnd_1_w.val() < 500) || (wnd_1_w.val() > 2200))) err_msg += "<div>Ширина первого окна указана не верно.</div>";
	if ((wnd_2_w.is(":visible")) && ((wnd_2_w.val() < 500) || (wnd_2_w.val() > 2200))) err_msg += "<div>Ширина второго окна указана не верно.</div>";
	if ((wnd_3_w.is(":visible")) && ((wnd_3_w.val() < 500) || (wnd_3_w.val() > 2200))) err_msg += "<div>Ширина третьего окна указана не верно.</div>";
	if ((balcony_w.is(":visible")) && ((balcony_w.val() < 500) || (balcony_w.val() > 1300))) err_msg += "<div>Ширина балконной двери указана не верно.</div>";
	if ((balcony_h.is(":visible")) && ((balcony_h.val() < 1800) || (balcony_h.val() > 2200))) err_msg += "<div>Высота балконной двери указана не верно.</div>";
	
	if ((wnd_h.is(":visible")) && (balcony_h.is(":visible")) && (parseInt(balcony_h.val()) < parseInt(wnd_h.val()))) err_msg += "<div>Высота окон больше высоты балконной двери</div>";
	
	if (err_msg == "")
	{
		$(".error_block").html("");
		return true;
	}
	else
	{
		$(".error_block").html("<b>Допущены ошибки в следующих полях:</b>"+err_msg);
		return false;
	}
	
}
function get_wnd_open_type(wnd_index)
{
	var open_type_id = "";
	
	if ((light_ver) && (((wnd_index == 1) && ($("#constr_1").attr("checked"))) || ((wnd_index == 2) && (($("#constr_2").attr("checked")) || ($("#constr_3").attr("checked"))))))
	{
		open_type_id = "t2";
	}
	else
		open_type_id = $("#open_type_"+wnd_index+" div[marked=1]").attr("id");
		
	if (open_type_id == "t1") return 0;
	if ((open_type_id == "t2") || (open_type_id == "t3")) return 1;
	if ((open_type_id == "t4") || (open_type_id == "t5")) return 2;
}

function get_txt_wnd_open_type(wnd_index)
{
	switch(get_wnd_open_type(wnd_index))
	{
		case 0: return "глухое"; break;
		case 1: return "поворотное"; break;
		case 2: return "поворотно-откидное"; break;
	}
}

function CalcPrice()
{
	if (!ValidateData())
	{
		$(".c_price_div").hide();
		return false;
	}
	
	// инициализируем переменную, в которой будет храниться состав заказа
	var total_order_text = "";
	// читстим скрытое поле для формы отправки
	$("#order_content").empty();
	
	var steklopaket = $("#steklopaket option:selected").val();
	var profil = $("#profil option:selected").val();
	var otliv = $("#otliv option:selected").val();
	var otkos = $("#otkos option:selected").val();
	var podokonnik = $("#podokonnik option:selected").val();
	
	if ($("#wnd_1 input").is(":visible")) { wnd_1_checked = true; w1 = parseInt($("#wnd_1 input").val()); wnd_h = parseInt($("#wnd_vert_size input").val()); } else wnd_1_checked = false;
	if ($("#wnd_2 input").is(":visible")) { wnd_2_checked = true; w2 = parseInt($("#wnd_2 input").val()); } else wnd_2_checked = false;
	if ($("#wnd_3 input").is(":visible")) { wnd_3_checked = true; w3 = parseInt($("#wnd_3 input").val()); } else wnd_3_checked = false;
	if ($("#balcony input").is(":visible")) { wnd_4_checked = true; w4 = parseInt($("#balcony input").val()); b_h = parseInt($("#balcony_vert_size input").val()); } else wnd_4_checked = false;
	
	if ($("#mont").is(":checked")) montaj = 1; else montaj = 0;
	
	// стоимость доставки
	S_dostavki = 1500;
	// так сказал admin
	S_dostavki = 0;

	dop_koef = K_steklopaket[steklopaket] * K_mont[montaj] * K_profil[profil];

	price = 0;
	total_wnd_width = 0;
	var wnd_selected = false;

	// окно 1 выбрано
	if (wnd_1_checked)
	{
		total_wnd_width += w1;
		S_okna = wnd_h * w1 / 1000 / 1000;
		price += S_okna * C_meter[get_wnd_open_type(1)] * dop_koef;
		
		total_order_text += "Ширина створки 1 = "+w1+" мм.";
		total_order_text += " Тип открывания: "+get_txt_wnd_open_type(1)+".";
		
		// учитываем цену москитной сетки, если окно не глухое
		if ((get_wnd_open_type(1) != 0) && ($("#open_type_1 .moskit").attr("moskit_on") == 1))
		{
			price += S_okna * C_metr_mosk_setka * K_mont[montaj];
			total_order_text += " Москитная сетка: да.";
		}
		total_order_text += "\n";
		wnd_selected = true;
	}

	// окно 2 выбрано
	if (wnd_2_checked)
	{
		total_wnd_width += w2;
		S_okna = wnd_h * w2 / 1000 / 1000;
		price += S_okna * C_meter[get_wnd_open_type(2)] * dop_koef;
		
		total_order_text += "Ширина створки 2 = "+w2+" мм.";
		total_order_text += " Тип открывания: "+get_txt_wnd_open_type(2)+".";
		
		// учитываем цену москитной сетки, если окно не глухое
		if ((get_wnd_open_type(2) != 0) && ($("#open_type_2 .moskit").attr("moskit_on") == 1))
		{
			price += S_okna * C_metr_mosk_setka * K_mont[montaj];
			total_order_text += " Москитная сетка: да.";
		}
		total_order_text += "\n";
		wnd_selected = true;
	}
	
	// окно 3 выбрано
	if (wnd_3_checked)
	{
		total_wnd_width += w3;
		S_okna = wnd_h * w3 / 1000 / 1000;
		price += S_okna * C_meter[get_wnd_open_type(3)] * dop_koef;
		
		total_order_text += "Ширина створки 3 = "+w3+" мм.";
		total_order_text += " Тип открывания: "+get_txt_wnd_open_type(3)+".";
		
		// учитываем цену москитной сетки, если окно не глухое
		if ((get_wnd_open_type(3) != 0) && ($("#open_type_3 .moskit").attr("moskit_on") == 1))
		{
			price += S_okna * C_metr_mosk_setka * K_mont[montaj];
			total_order_text += " Москитная сетка: да.";
		}
		total_order_text += "\n";
		wnd_selected = true;
	}
	
	// дверь выбрана
	if (wnd_4_checked)
	{
		total_order_text += "Ширина двери = "+w4+" мм.\n";
		total_order_text += "Высота двери = "+b_h+" мм.\n";
		
		S_okna = b_h * w4 / 1000 / 1000;
		price += S_okna * C_meter[2] * dop_koef;
	}

	if (wnd_selected)
	{
		total_order_text = "Высота окон = "+wnd_h+" мм.\n"+total_order_text+"-----\n";
		
		// учитываем стоимость подоконников
		price += (total_wnd_width + 200) / 1000 * C_podokonnik[podokonnik];
		total_order_text += "Ширина подоконника = "+$("#podokonnik option:selected").text()+".\n";
		
		// учитываем стоимость откосов
		price += (wnd_h * 2 + total_wnd_width + 500) / 1000 * C_otkos[otkos];
		total_order_text += "Ширина откоса = "+$("#otkos option:selected").text()+".\n";
		
		// учитываем стоимость отливов
		price += total_wnd_width / 1000 * C_otliv[otliv];
		total_order_text += "Ширина отлива = "+$("#otliv option:selected").text()+".\n";
	}

	total_order_text = "Тип стеклопакета: "+$("#steklopaket option:selected").text()+"\n"+total_order_text;
	total_order_text = "Тип профиля: "+$("#profil option:selected").text()+"\n"+total_order_text;
	
	if (montaj == 1)
		total_order_text += "Необходим монтаж: да.";
		
	// учитываем стоимость доставки
	price += S_dostavki;
	
	total_order_text += "\nОбщая сумма заказа: " + Math.round(price)+" грн.";
	
	$("#order_content").text(total_order_text);
	
	return Math.round(price)+" <span>грн.</span>";
}

$(document).ready(function(){
	// выбираем тип конструкции
	$(".constr_type_table img").click(function(){
		cur_constr_type_id = $(this).attr("constr_type");
		if (old_constr_type_id != cur_constr_type_id)
		{
			$("#constr_"+$(this).attr("constr_type")).attr("checked", "checked");
			ShowConstr(cur_constr_type_id);
			old_constr_type_id = cur_constr_type_id;
		}
	});
	$(".constr_type_table input[name=small_wnd_radio]").click(function(){
		cur_constr_type_id = $(this).attr("id").replace("constr_", "");
		if (old_constr_type_id != cur_constr_type_id)
		{
			ShowConstr(cur_constr_type_id);
			old_constr_type_id = cur_constr_type_id;
		}
	});
	
	// подсвечиваем иконку, показывающую тип открывания
	$(".open_type .t").mouseover(function(){
		if ($(this).attr("marked") != 1) OpenTypeIcoCss($(this), "#BFBFBF");
	});
	$(".open_type .t").mouseout(function(){
		if ($(this).attr("marked") != 1) OpenTypeIcoCss($(this), "");
	});
	
	// меняем тип открывания
	$(".open_type .t").click(function(){
		if ($(this).attr("marked") == 1) return;
		
		var wnd_id = $(this).parent().attr("wnd_id");
		
		// рисуем выбранное
		$("#wnd_"+wnd_id+" .window div[name='open_type']").hide();
		
		var moskit_btn = $(".open_type[wnd_id='"+wnd_id+"'] .moskit");
		if ($(this).attr("id") == "t1") { moskit_btn.removeClass("m_sel"); moskit_btn.addClass("m_inactive"); }
		else { moskit_btn.removeClass("m_inactive"); }
		
		switch($(this).attr("id"))
		{
			case "t1": $("#wnd_"+wnd_id+" .moskit").hide(); moskit_btn.attr("moskit_on", 0); break;
			case "t2": $("#wnd_"+wnd_id+" .left_open").show(); break;
			case "t3": $("#wnd_"+wnd_id+" .right_open").show(); break;
			case "t4": $("#wnd_"+wnd_id+" .left_open").show(); $("#wnd_"+wnd_id+" .top_open").show(); break;
			case "t5": $("#wnd_"+wnd_id+" .right_open").show(); $("#wnd_"+wnd_id+" .top_open").show(); break;
		}
		
		// снимаем выделение со старой иконки
		var old_ico = $(this).parent().find("[marked=1]");
		old_ico.css({ border: "0px", top: parseInt(old_ico.css("top"))+1, left: parseInt(old_ico.css("left"))+1 }); 
		old_ico.attr("marked", "0");
		
		// ставим выделение на новую иконку
		$(this).css({ borderColor: "#999" });
		$(this).attr("marked", "1");
	});
	
	// подчеркивание ссылки на переключателе москитной сетки
	$(".open_type .moskit").mouseover(function(){
		if ($(this).attr("class") == "moskit m_lnk") $(this).css({ textDecoration: "none" }); 
	});
	
	$(".open_type .moskit").mouseout(function(){
		if ($(this).attr("class") == "moskit m_lnk") $(this).css({ textDecoration: "underline" }); 
	});

	// добавляем/удаляем москитную сетку
	$(".open_type .moskit").click(function(){
		var wnd_id = $(this).parent().attr("wnd_id");
		
		// у глухого окна не может быть москитной сетки
		if ($(this).parent().find("[marked='1']").attr("id") == "t1") return;

		if ($(this).attr("moskit_on") == 1)
		{
			$("#wnd_"+wnd_id+" .moskit").hide();
			$(this).attr("moskit_on", 0);
		}
		else
		{
			$("#wnd_"+wnd_id+" .moskit").show();
			$(this).attr("moskit_on", 1);
		}
		$(this).toggleClass("m_sel");
	});
	
	// показываем калькулятор в начальном состоянии
	$("#constr_4").attr("checked", "checked");
	ShowConstr("4");
	
	$("#recalc_btn").click(function(){
		price = CalcPrice();
		if (price)
		{
			$(".c_price_div").show();
			$(".c_price_div .c_price_span").html(price);
			calc_res_ok = true;
		}
	});
	
	$(".windows_block input").focus(function(){$(this).parent().find("#input_hint").fadeIn("fast");});
	$(".windows_block input").blur(function(){$(this).parent().find("#input_hint").fadeOut("fast");});
	
	$("#wnd_vert_size input").keyup(function(){     if ($(this).val() < 500 || $(this).val() > 2300) $(this).css("color", "#F00"); else $(this).css("color", "#000"); });
	$("#wnd_1 input").keyup(function(){             if ($(this).val() < 500 || $(this).val() > 2200) $(this).css("color", "#F00"); else $(this).css("color", "#000"); });
	$("#wnd_2 input").keyup(function(){             if ($(this).val() < 500 || $(this).val() > 2200) $(this).css("color", "#F00"); else $(this).css("color", "#000"); });
	$("#wnd_3 input").keyup(function(){             if ($(this).val() < 500 || $(this).val() > 2200) $(this).css("color", "#F00"); else $(this).css("color", "#000"); });
	$("#balcony input").keyup(function(){           if ($(this).val() < 500 || $(this).val() > 1300) $(this).css("color", "#F00"); else $(this).css("color", "#000"); });
	$("#balcony_vert_size input").keyup(function(){ if ($(this).val() < 1800 || $(this).val() > 2200) $(this).css("color", "#F00"); else $(this).css("color", "#000"); });
	
	$("#order_btn").click(function(){
		if (calc_res_ok)
		{
			wnd_count = 0;
			if ($("#wnd_1 input").is(":visible")) wnd_count += 1;
			if ($("#wnd_2 input").is(":visible")) wnd_count += 1;
			if ($("#wnd_3 input").is(":visible")) wnd_count += 1;
			order_wnd_content = "Количество окон: "+wnd_count;
		
			$("#order_wnd_content").val(order_wnd_content);
		}
		else
			$("#order_wnd_content").val("Пользователь не указал параметры окна.");
	});
	
	// тестовые данные
	ShowTestData();
});