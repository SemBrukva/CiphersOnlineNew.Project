{* Встраиваемый виджет инструмента: только калькулятор, без примеров/FAQ/связанных. *}
{* Флаг $embed=true скрывает кнопки «в избранное» и «Встроить» внутри _tool_widget.tpl. *}
{if $tool_ui.diffMode|default:false}
{include file="cipher/_diff_widget.tpl" cipher=$cipher tool_slug=$tool_slug tool_ui=$tool_ui tool_ui_json=$tool_ui_json embed=true}
{else}
{include file="cipher/_tool_widget.tpl" cipher=$cipher tool_slug=$tool_slug tool_ui=$tool_ui tool_ui_json=$tool_ui_json embed=true}
{/if}
