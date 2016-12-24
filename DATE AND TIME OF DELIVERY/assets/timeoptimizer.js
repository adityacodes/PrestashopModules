
$(document).ready(function(){


	var array = new Array();
	var result = new Array();
	var date;
	var serverTime = $("#servertime").val();
	var serverDate = $("#serverdate").val();

	$('#delivery_time option').each(function(){

		array.push( $(this).val().split('-') );

	});

	$.each(array, function(index , value){

			$.each(value, function(index1 , value1){

				var x = value1.replace(/\d+/g, '');

				value1 = convertToTFH(x, value1);

				result.push(value1);

			});

	});
	//console.log(result);

	var final = new Array();

	for (var i=0 ; i< result.length ; i+=2) {

		var temp = { mintime: result[i], maxtime: result[i+1] }

		final.push(temp);

	}
	//console.log(final);

	

	$("#datepicker").change(function(){
		  date = $("#datepicker").val();

		  if( date == serverDate )
		  {
		  	// Limit Timings and change options accordingly
		  	limitTime(serverTime, final);
		  }
		  else{
		  	//Donot limit timings.
		  	nolimitations(final);
		  }
	});

	function nolimitations(final){

		var savedoptions = new Array();
		$.each(final, function(index2, value2){
			savedoptions.push(convertToTWH(value2.mintime) +'-'+ convertToTWH(value2.maxtime));
		});
		$('#delivery_time').empty();
		$.each(savedoptions, function(index3, value3){
			//console.log('<option value="'+value3+'">'+value3+'</option>');
			$('#delivery_time').append('<option value="'+value3+'">'+value3+'</option>');
		});
		$('#delivery_time').change();
		
	}


	function convertToTFH(x, value1){

				if( x.trim() == 'PM' && parseInt(value1) != 12 )
					return value1 = parseInt(value1) + 12;

				else if( x.trim() == 'AM' && parseInt(value1) != 12 )
					return value1 = parseInt(value1);

				else if( x.trim() == 'AM' && parseInt(value1) == 12 )
					return value1 = 0;
				else
					return value1 = 12;
	}

	function convertToTWH(time){

				if( time > 12 )
					return	time = (time - 12) + " PM";

				else if( time < 12 && time != 0 )
					return	time = time + " AM";

				else if( time == 12 )
					return time = time + " PM";

				else
					return time = "12 AM";
	}


	function limitTime(serverTime, final){

			var resultset = new Array();

			

			$.each(final, function(index2, value2){

				if( serverTime >= 0 && serverTime <6 ){
					if(value2.mintime>=6 )
						resultset.push(convertToTWH(value2.mintime) +" - "+ convertToTWH(value2.maxtime));
				}
				else if ( serverTime >= 6 && serverTime < 9 ){
					if(value2.mintime>=9)
						resultset.push(convertToTWH(value2.mintime) +" - "+ convertToTWH(value2.maxtime));
				}
				else if( serverTime >=9 && serverTime < 12 ){
					if(value2.mintime>=12)
						resultset.push(convertToTWH(value2.mintime) +" - "+ convertToTWH(value2.maxtime));
				}
				else if( serverTime >= 12 && serverTime < 15 ){
					if(value2.mintime>=15)
						resultset.push(convertToTWH(value2.mintime) +" - "+ convertToTWH(value2.maxtime));
				}
				else if(serverTime >=15 && serverTime <18 ){
					if(value2.mintime>=18)
						resultset.push(convertToTWH(value2.mintime) +" - "+ convertToTWH(value2.maxtime));
				}
				else{
					if(value2.mintime>=21)
						resultset.push(convertToTWH(value2.mintime) +" - "+ convertToTWH(value2.maxtime));
				}

			});
			//console.log(resultset);

			$('#delivery_time').empty();

			$.each(resultset, function(index3, value3){
				//console.log('<option value="'+value3+'">'+value3+'</option>');
				$('#delivery_time').append('<option value="'+value3+'">'+value3+'</option>');
			});

			$('#delivery_time').change();


	}

	

	var saved = $('#delivery_date').val();
	$('#delivery_date').load(function(){
		$(this).val(saved);
	});

	


});



	
