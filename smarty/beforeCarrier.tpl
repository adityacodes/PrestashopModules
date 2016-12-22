
<div class="wells col-sm-12" style="margin:0; ">

	<p>
		<p style="font-weight: bold; color:#333; margin-left: 0px;">Select Date And Time Of Delivery <sup>*</sup></p>

<br>
	{if $datenotset}
		<h4 style="color: red;" class="text-center"><strong>DATE NOT SET.</strong></h4>
	{/if}

	{if $timenotset}
		<h4 style="color: red;" class="text-center"><strong>TIME NOT SET.</strong></h4>
	{/if}
<h5>
		<div class="form-group col-sm-6">
		  <label class="col-sm-5" for="sel1">Expected Date Of Delivery:</label>
		  <div class="col-sm-7">
				 <div class="input-group">
				    <span class="input-group-addon"><img style="height: 14px;" src="{$timeicon}"></span>
				    <input name="delivery_date" id="datepicker" type="text" value="{$dateval}" class="form-control input-lg" placeholder="Select Date...">
				  </div>
			</div>
		</div>


		<div class="form-group form-group-lg col-sm-6">
		  <label class="col-sm-5" for="sel1">Expected Time Of Delivery:</label>
		  <div class="col-sm-7">
			  <select name="delivery_time" id="delivery_time" class="form-control form-control-lg" id="sel1">
			    {if isset($rules)}
					{foreach from=$rules key=k item=v}

						{assign var="time" value="{$v['minimal_time']}-{$v['maximal_time']}"}

						<option value="{$v['minimal_time']}-{$v['maximal_time']}" 
						 {if $timeval eq $time} selected {/if} >{$v['minimal_time']} - {$v['maximal_time']}</option>
					{/foreach}
				{/if}
			  </select>
			</div>
		</div>
</h5>		
	</p>


	

</div><br><br><br>
<input type="text" id="servertime" value="{$servertime}" hidden>
<input type="text" id="serverdate" value="{$serverdate}" hidden>

<script>
	$( function() {
	    $( "#datepicker" ).datepicker({ minDate: '{$serverdate}', maxDate: "{$maxDate}" });
	  } );

$( function(){

	$("button[name='processCarrier']").click(function(){
		console.log("Hello I am called");
			return chooseDateTime();
	});

});

function chooseDateTime()
{
	if( $('#datepicker').val().length === 0 )
	{
		if (!!$.prototype.fancybox)
		    $.fancybox.open([
	        {
	            type: 'inline',
	            autoScale: true,
	            minHeight: 30,
	            content: '<p class="fancybox-error">' + "Delivery date and time not set." + '</p>'
	        }],
			{
		        padding: 0
		    });
		else
		    alert("Delivery Date and time not set.");
	}
	else
		return true;

	return false;
}
</script>