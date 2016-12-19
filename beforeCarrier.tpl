
<div class="wells col-sm-12" style="margin:0; ">
	<p>
		<p style="font-weight: bold; color:#333; margin-left: 0px;">Select Date And Time Of Delivery <sup>*</sup></p>

<br>
<h5>
		<div class="form-group col-sm-6">
		  <label class="col-sm-5" for="sel1">Expected Date Of Delivery:</label>
		  <div class="col-sm-7">
				 <div class="input-group">
				    <span class="input-group-addon"><img style="height: 14px;" src="{$timeicon}"></span>
				    <input id="datepicker" type="text" class="form-control input-lg" placeholder="Select Date...">
				  </div>
			</div>
		</div>


		<div class="form-group form-group-lg col-sm-6">
		  <label class="col-sm-5" for="sel1">Expected Time Of Delivery:</label>
		  <div class="col-sm-7">
			  <select class="form-control form-control-lg" id="sel1">
			    {if isset($rules)}
					{foreach from=$rules key=k item=v}
						<option>{$v['minimal_time']} - {$v['maximal_time']}</option>
					{/foreach}
				{/if}
			  </select>
			</div>
		</div>
</h5>		
	</p>


	

</div><br><br><br>


<script>
	$( function() {
	    $( "#datepicker" ).datepicker({ minDate: '{$serverdate}', maxDate: "{$maxDate}" });
	  } );
</script>