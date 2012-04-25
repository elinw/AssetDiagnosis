<?php
/**
 * A JApplicationWeb application built on the Joomla Platform.
 *
 * To run this place it in the root of your Joomla CMS installation.
 * This application runs some tests for common problems in the #__assets table.
 *
 * @package    Joomla.AssetDiagnosis
 * @copyright  Copyright (C) 2005 - 2011 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

ini_set('display_errors','1');

// Set flag that this is a parent file.
// We are a valid Joomla entry point.
define('_JEXEC', 1);

// Setup the base path related constants.
define('JPATH_BASE', dirname(__FILE__));
define('JPATH_SITE', JPATH_BASE);
define('JPATH_CONFIGURATION',JPATH_BASE);

// Bootstrap the application.
require JPATH_BASE . '/libraries/import.php';


// Import the JApplicationWeb class from the platform.
jimport('joomla.application.web');

/**
 * This class checks some common situations that occur when the asset table is corrupted.
 */
// Instantiate the application.
class AssetDiagnosis extends JApplicationWeb
{
	/**
	 * Overrides the parent doExecute method to run the web application.
	 *
	 * This method should include your custom code that runs the application.
	 *
	 * @return  void
	 *
	 * @since   11.3
	 */

	public function __construct()
	{
		// Call the parent __construct method so it bootstraps the application class.
		parent::__construct();
		require_once JPATH_CONFIGURATION.'/configuration.php';

		jimport('joomla.database.database');
		// System configuration.

		$config = JFactory::getConfig();
		// Note, this will throw an exception if there is an error
		// Creating the database connection.
		$this->dbo = JDatabase::getInstance(
			array(
				'driver' => $config->get('dbtype'),
				'host' => $config->get('host'),
				'user' => $config->get('user'),
				'password' => $config->get('password'),
				'database' => $config->get('db'),
				'prefix' => $config->get('dbprefix'),
			)
		);

	}
	protected function doExecute()
	{
		// Initialise the body with the DOCTYPE.
		$this->setBody(
			'<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'
		);

		$this->appendBody('<html>')
			->appendBody('<head>')

			->appendBody('</head>')
			->appendBody('<body style="font-family:verdana; margin-left: 30px; width: 500px;">');


		$this->appendBody('<h1>Asset Table Diagnostics</h1>
		<p>This is an unofficial way of checking for problems in the asset tables of Joomla. It may not catch
		every problem, but it does give you some indication of where problems may exist.</p>
		<ul>
			<li>
				<p>Is there any asset besides root with parent_id of 0? If no things  are good, if yes there is a problem.</p>
				<dl>
				<dt></dt>Asset Table:</dt><dd>');
				$query = $this->dbo->getQuery(true);
				$query->select('count(a.id)');
				$query->from('#__assets AS a');
				$query->where('a.parent_id = 0 AND a.name <> ' . $this->dbo->q('root.1'));
				$this->dbo->setQuery($query);
				if ( $this->dbo->loadResult() == 0 )
					{ $this->appendBody( 'No');}
					else
					{
						$this->appendBody(' Yes') ;
					}

				$this->appendBody('</dd></dl>');
				$this->appendBody('
				<dt>
				Category Table:</dt><dd>');
				$query = $this->dbo->getQuery(true);
				$query->select( 'count(a.id)');
				$query->from( $this->dbo->qn('#__categories'). ' AS a');
				$query->where('a.parent_id = 0');

				$this->dbo->setQuery($query);
				if ($this->dbo->loadResult() == 1 )
					{ $this->appendBody( 'No');}
					else
					{
						$this->appendBody('Yes') ;
					}
			$this->appendBody('</dd></dl>');
			$this->appendBody('</li>
			<li>
				<p>Is there any category without an asset in the asset table?</p>
				<p>If any extension is listed below it has at least one category without an asset.</p>
				<dl>');
				$query = $this->dbo->getQuery(true);
				$query->select('c.id, c.extension');
				$query->from($this->dbo->qn('#__categories') . ' AS c');
				$query->leftJoin('#__assets' .' AS a ON c.asset_id = a.id');
				$query->where('a.id IS NULL AND c.asset_id <> 0');
				$this->dbo->setQuery($query);
				$extensions = $this->dbo->loadObjectList();

				foreach($extensions as $extension):
					 $this->appendBody('<dt>'. $extension->extension . '</dt>');

					$prefix = '';
					if ($extension->extension == 'com_users') $prefix = '.notes';
					$this->dbo->setQuery($query);
					//$this->appendBody('<dd>'. $this->dbo->loadResult() . ' categories </dd>');

				endforeach;
				$this->appendBody('<p>If any extensions are listed, try clicking the rebuild icon in any category manager.
				Then refresh this page to see if the issue has been corrected.</p>');
				$this->appendBody('</dl>');
				$this->appendBody('</li>
			<li>
				<p>Is there any article without an asset in the asset table? if yes we have a problem, if no things are good</p>');

				$query = $this->dbo->getQuery(true);
				$query-> select('count(c.id) ');
				$query->from( $this->dbo->qn('#__content') .'AS c');
				$query->leftJoin($this->dbo->qn('#__assets') .'AS a ON c.asset_id = a.id');
				$query->where('a.id IS NULL');
				$this->dbo->setQuery($query);
				$this->appendBody('<dl><dd>');
				if ($this->dbo->loadResult() == 0 )
					{
						$this->appendBody( ' No');
					}
					elseif ($this->dbo->loadResult() == 1)
					{
						$this->appendBody(' Yes: There is 1 article with no asset.') ;
					}
					else
					{
						$this->appendBody(' Yes: There are ' . $this->dbo->loadResult() .' articles with no assets.') ;
					}
				$this->appendBody('</dd></dl>');
				$this->appendBody('<p>If there are a small number of articles without assets you should be able to
				correct this by opening and saving each one. With a large number you may want to use bulk copy to
				trigger asset creation.</p>');

			$this->appendBody('</li>
			<li>
				<p>Is there any category with an asset level of < 2? If  yes, there is a problem, if no, things are good</p>
				<dl><dd>');

					$query = $this->dbo->getQuery(true);
					$query->select('count(a.id)' );
					$query->from('#__assets as a');
					$query->where('a.name LIKE '.  $this->dbo->q('%category%') . ' AND level  < 2 ');
					$this->dbo->setQuery($query);
				if ($this->dbo->loadResult() == 0 )
					{
						$this->appendBody('No');}
					else
					{
						$this->appendBody('Yes') ;
					}

					$this->appendBody('
					</dd>');

				$this->appendBody('</dl>');
				$this->appendBody('<p>If the answer is yes, try clicking the rebuild icon in any category manager.
				Then refresh this page to see if the issue has been corrected. If it
				still has an a problem you may need to open each category and save it.</p>');

			$this->appendBody('</li>
			<li>
				<p>Is there any article that has an asset level of < 3? If yes bad, if no good</p>
				<dl><dd>');
				$query = $this->dbo->getQuery(true);
				$query->select('count(a.id) FROM #__assets a ');
				$query->where('a.level < 3 AND a.name LIKE ' . $this->dbo->q('%com_content.article.%'));
				$this->dbo->setQuery($query);
				if ($this->dbo->loadResult() == 0 )
				{
					$this->appendBody( 'No');
				}
				elseif ($this->dbo->loadResult() == 1 )
				{
					$this->appendBody( 'Yes ');
				}
				else
				{
					$this->appendBody('Yes: 1 article') ;
				}
			$this->appendBody('</dd></dl>');
			$this->appendBody('<p>If you have a small number of problem articles, try opening and saving each article.
			For larger numbers you may want to use bulk copy into a new category and then back to the old one.</p>');
			$this->appendBody('</li>
			<li>
		<p>Is there any asset for an article that has a parent_id that does not correspond to a category? if yes there is a problem,
		 if no things are good.</p>');

		$query = $this->dbo->getQuery(true);
		$query->select('a.parent_id');
		$query->from($this->dbo->qn('#__assets') . ' as a ');
		$query->leftJoin($this->dbo->qn( '#__categories')
			. ' on a.parent_id = asset_id');
		 $query->where(' a.name LIKE '. $this->dbo->q('%com_content.article.%')
			.'AND (  extension <> '. $this->dbo->q('com_content') .'  OR extension is null)');

		$this->dbo->setQuery($query);
		$results = $this->dbo->loadResult();
		$this->appendBody('<dl><dd>');
		if ($results)
		{
			$this->appendBody('Yes at least one article has this issue');
		}
		else
		{
			$this->appendBody('No article has this issue.');
		}
		$this->appendBody('</dd>');

		$this->appendBody('</dl>');
		$this->appendBody('</li>
		</ul>');
		// Finished up the HTML repsonse.
		$this->appendBody('</body>')
			->appendBody('</html>');
	}
}
JWeb::getInstance('AssetDiagnosis')->execute();


