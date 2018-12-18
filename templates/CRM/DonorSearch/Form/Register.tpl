{* HEADER *}

<table class="form-layout-compressed">
  <tr>
    <td>
      <div class="messages help">
        <p>Register your DonorSearch API key in CiviCRM (which will be used for DonorSearch API authentication )<br/>
         either by submitting your account credential OR existing API key below:</p>
       </div>
     </td>
   </tr>
   <tr>
     <td>
       <div class="crm-accordion-wrapper crm-ajax-accordion {if $collapsible}collapsed{/if}">
         <div class="crm-accordion-header">Account credential</div>
         <div class="crm-accordion-body">
           <div>
             <table class="form-layout-compressed">
               <tr>
                 <td>{$form.user.label}</td>
                 <td>{$form.user.html}</td>
               </tr>
               <tr>
                 <td>{$form.pass.label}</td>
                 <td>{$form.pass.html}</td>
               </tr>
             </table>
           </div>
         </div>
       </div>
     </td>
   </tr>
   <tr>
     <td><center><b>OR</b></center></td>
   </tr>
   <tr>
     <td>
       <div class="crm-accordion-wrapper crm-ajax-accordion">
         <div class="crm-accordion-header">DonorSearch API Key</div>
         <div class="crm-accordion-body">
           <div>
             <table class="form-layout-compressed">
               <tr>
                 <td>
                   {$form.api_key.label}
                 </td>
                 <td>
                   {$form.api_key.html}
                 </td>
               </tr>
             </table>
           </div>
         </div>
       </div>
     </td>
   </tr>
</table>

{* FOOTER *}
<div class="crm-submit-buttons">
{include file="CRM/common/formButtons.tpl" location="bottom"}
</div>
