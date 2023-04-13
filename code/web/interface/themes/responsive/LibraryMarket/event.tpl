<div class="col-xs-12">
	<div class="row">
		<div class="col-sm-12">
			<h1>{$recordDriver->getTitle()}</h1>
		</div>
	</div>
	<div class="row">
		<div class="col-sm-4">
			{if !empty($recordDriver->getEventCoverUrl())}
				<div class="panel active">
					<div class="panel-body">
						<a href="{$recordDriver->getLinkUrl()}"><img class="img-responsive img-thumbnail {$coverStyle}" src="{$recordDriver->getEventCoverUrl()}" alt="{$recordDriver->getTitle()|escape}"></a>
					</div>
				</div>
			{/if}
			{if !empty($recordDriver->getAudiences())}
				<div class="panel active">
					<div class="panel-heading">
						{translate text="Audience" isPublicFacing=true}
					</div>
					<div class="panel-body">
						{foreach from=$recordDriver->getAudiences() item=audience}
							<div class="col-xs-12">
								<a href='/Events/Results?filter[]=age_group_facet%3A"{$audience|escape:'url'}"'>{$audience}</a>
							</div>
						{/foreach}
					</div>
				</div>
			{/if}
		</div>
		<div class="col-sm-4">
				<ul>
					<li>Date: {$recordDriver->getStartDate()|date_format:"%A %B %e, %Y"}</li>
					<li>Time: {$recordDriver->getStartDate()|date_format:"%l:%M %p"} to {$recordDriver->getEndDate()|date_format:"%l:%M %p"}</li>
					<li>Branch: {$recordDriver->getBranch()}</li>
				</ul>
		</div>
		<div class="col-sm-4" style="display:flex; justify-content:center;">
			{if $recordDriver->isRegistrationRequired()}
				<a class="btn btn-primary"  onclick="return AspenDiscovery.Account.saveEventReg(this, 'Events', '{$recordDriver->getUniqueID()|escape}', '{$recordDriver->getExternalUrl()}');">
					<i class="fas fa-external-link-alt"></i>
					{translate text=" Add to Your Events and Register" isPublicFacing=true}
				</a>
			{else}
				<a class="btn btn-primary" onclick="return AspenDiscovery.Account.saveEvent(this, 'Events', '{$recordDriver->getUniqueID()|escape}');">{translate text="Add to Your Events" isPublicFacing=true}</a>
			{/if}
		</div>
			<br>
		<div class="col-sm-8">
			<div class="btn-group btn-group-sm">
				<a href="{$recordDriver->getExternalUrl()}" class="btn btn-sm addtolistlink addToListBtn" target="_blank"><i class="fas fa-external-link-alt"></i> {translate text="More Info" isPublicFacing=true}</a>
				<button onclick="return AspenDiscovery.Account.showSaveToListForm(this, 'Events', '{$recordDriver->getUniqueID()|escape}');" class="btn btn-sm addtolistlink addToListBtn">{translate text="Add to list" isPublicFacing=true}</button>
			</div>
			<div class="btn-group btn-group-sm">
				{include file="Events/share-tools.tpl" eventUrl=$recordDriver->getExternalUrl()}
			</div>
			<br>
			<br>
		</div>
	</div>
	<div class="row">
		<div class="col-sm-8 col-sm-offset-4">
				{$recordDriver->getDescription()}
		</div>
	</div>
	<div class="row">
		<div class="col-sm-12">
			{if !empty($loggedIn) && (in_array('Administer LibraryMarket LibraryCalendar Settings', $userPermissions))}
				<div id="more-details-accordion" class="panel-group">
					<div class="panel" id="staffPanel">
						<a data-toggle="collapse" href="#staffPanelBody">
							<div class="panel-heading">
								<div class="panel-title">
									<h2>{translate text=Staff isPublicFacing=true}</h2>
								</div>
							</div>
						</a>
						<div id="staffPanelBody" class="panel-collapse collapse">
							<div class="panel-body">
								<h3>{translate text="LibraryMarket LibraryCalendar Event API response" isPublicFacing=true}</h3>
								<pre>{$recordDriver->getStaffView()|print_r}</pre>
							</div>
						</div>
					</div>
				</div>
			{/if}
		</div>
	</div>
</div>