<!-- MODULE Block new products -->
<div id="viewed-products_block_left" class="block products_block">
	<h4><a href="{$base_dir}new-products.php" title="{l s='New products' mod='blocknewproductz'}">{l s='New products' mod='blocknewproductz'}</a></h4>
	<div class="block_content">
	{if $new_products !== false}
		<ul class="products clearfix">
		{foreach from=$new_products item='product' name='newProducts'}
				<li class="clearfix{if $smarty.foreach.newProducts.last} last_item{elseif $smarty.foreach.newProducts.first} first_item{else} item{/if}">
					<a href="{$product.link}" title="{$product.legend|escape:html:'UTF-8'}"><img src="{$link->getImageLink($product.link_rewrite, $product.id_image, 'medium')}" height="{$mediumSize.height}" width="{$mediumSize.width}" alt="{$product.legend|escape:html:'UTF-8'}" /></a>
					<h5><a href="{$product.link}" title="{$product.name|escape:html:'UTF-8'}">{$product.name|escape:html:'UTF-8'|truncate:14:'...'}</a></h5>
					<p><a href="{$product.link}">{$product.description_short|strip_tags:'UTF-8'|truncate:50:'...'}</a></p>
        </li>
		{/foreach}
		</ul>
		<p><a href="{$base_dir}new-products.php" title="{l s='All new products' mod='blocknewproductz'}" class="button_large">{l s='All new products' mod='blocknewproductz'}</a></p>
	{else}
		<p>{l s='No new products at this time' mod='blocknewproductz'}</p>
	{/if}
	</div>
</div>
<!-- /MODULE Block new products -->
