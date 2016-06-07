<?php
/**
 * @package     BlueAcorn\Core
 * @version     1.0.0
 * @author      Sam Tay @ Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\Core\Console\Script;

use Magento\Framework\Filter\FilterManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command for generating product install scripts
 */
class ProductAttributeCommand extends Command
{
    const COMMAND_PRODUCT_SCRIPT_GENERATOR = 'dev:attribute-script:product';
    const ARGUMENT_CSV_FILE = 'csv';
    const OPTION_VERSION = 'setup-version';
    const OPTION_MODULE_NAME = 'module-name';

    const INSTALL = 'install';
    const UPGRADE = 'upgrade';

    const DEFAULT_VERSION = '1.0.0';
    const DEFAULT_MODULE_NAME = 'ProductIntegration';

    const CLASS_CONSTANT_PATTERN = '/^([a-zA-Z_\\\\]+)::([a-zA-Z0-9_]+)$/';

    /**
     * Install vs Upgrade flag
     * @var string
     */
    protected $format = '';

    /**
     * Template variables for install vs upgrade
     * @var array
     */
    protected $templateVars = [
        self::INSTALL => [
            'filename' => 'InstallData.php',
            'class' => 'InstallData',
            'interface' => 'InstallDataInterface',
            'method' => 'install'
        ],
        self::UPGRADE => [
            'filename' => 'UpgradeData.php',
            'class' => 'UpgradeData',
            'interface' => 'UpgradeDataInterface',
            'method' => 'upgrade'
        ]
    ];

    /**
     * Flat array of available attribute options
     * @see Magento\Catalog\Model\ResourceModel\Eav\Attribute\PropertyMapper
     * @see Magento\Catalog\Model\ResourceModel\Setup\PropertyMapper
     * @see Magento\ConfigurableProduct\Model\ResourceModel\Setup\PropertyMapper
     * @var array
     */
    protected $attributeOptions = [
        'backend',
        'type',
        'table',
        'frontend',
        'input',
        'label',
        'frontend_class',
        'source',
        'required',
        'user_defined',
        'default',
        'unique',
        'note',
        'global',
        'input_renderer',
        'visible',
        'searchable',
        'filterable',
        'comparable',
        'visible_on_front',
        'wysiwyg_enabled',
        'is_html_allowed_on_front',
        'visible_in_advanced_search',
        'filterable_in_search',
        'used_in_product_listing',
        'used_for_sort_by',
        'apply_to',
        'position',
        'used_for_promo_rules',
        'is_used_in_grid',
        'is_visible_in_grid',
        'is_filterable_in_grid',
        'is_configurable',
        'group',
    ];

    /**
     * Methods accept value and return true if quotes should be EXCLUDED
     * @var callable[]
     */
    protected $skipWrappingQuotesCheck = ['self::_isBool', 'self::_isClassConstant'];

    /**
     * Holds mapping [columnIndex => attributeOption]
     * @var array
     */
    protected $columnToOptionLink = [];

    /**
     * Holds column index of 'name' option
     * @var int
     */
    protected $nameIndex;

    /**
     * @var FilterManager
     */
    protected $filterManager;

    /**
     * @param FilterManager $filterManager
     * @internal param null|string $name
     */
    public function __construct(
        FilterManager $filterManager,
        $name = null
    ) {
        parent::__construct($name);
        $this->filterManager = $filterManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $csvFilename = $input->getArgument(self::ARGUMENT_CSV_FILE);
        $version = $input->getOption(self::OPTION_VERSION) ?: self::DEFAULT_VERSION;
        $moduleName = $input->getOption(self::OPTION_MODULE_NAME) ?: self::DEFAULT_MODULE_NAME;
        $this->format = $input->getOption(self::OPTION_VERSION) ? self::UPGRADE : self::INSTALL;

        $outputFilename = implode(DIRECTORY_SEPARATOR, [
            BP,
            'var',
            'import_export',
            'product_attribute_scripts',
            $this->templateVars[$this->format]['filename']
        ]);

        $rows = $this->parseCsvMetadata($csvFilename);
        $body = $this->getBody($rows);
        if ($this->format == self::UPGRADE) {
            $this->wrapVersionChecker($body);
        }
        list($header, $footer) = $this->getHeaderFooter();

        $templateVars = array_merge(
            $this->templateVars[$this->format],
            ['module_name' => $moduleName, 'version' => $version]
        );
        $script = $this->filterManager->template(
            $header . $body . $footer,
            ['variables' => $templateVars]
        );

        $this->writeToFile($outputFilename, $script);
        $output->writeln("<info>Script successfully generated to {$outputFilename}</info>");
    }

    /**
     * Writes content to output file, creates parent directories if necessary
     *
     * @param $outputFilename
     * @param $content
     * @throws \Exception
     */
    protected function writeToFile($outputFilename, $content)
    {
        $directory = substr($outputFilename, 0, strrpos($outputFilename, DIRECTORY_SEPARATOR));
        if (!is_dir($directory)) {
            mkdir($directory, 0700, true);
        }
        $scriptFile = fopen($outputFilename, 'w');
        if (!$scriptFile) {
            throw new \Exception("Couldn't open output file");
        }
        fwrite($scriptFile, $content);
        fclose($scriptFile);
    }

    /**
     * Get body of install script
     *
     * @param array $rows
     * @return string
     */
    protected function getBody(array $rows)
    {
        $body = '';
        $template = $this->getAddAttributeTemplate();
        foreach($rows as $row) {
            if (empty($row[$this->nameIndex])) {
                continue; // filter any rows that aren't actual attributes
            }
            $vars = ['attribute_code' => $row[$this->nameIndex]];
            foreach($this->columnToOptionLink as $columnIndex => $option) {
                $optionValue = $row[$columnIndex];
                $noQuotes = array_reduce($this->skipWrappingQuotesCheck, function($carry, $checkMethod) use($optionValue) {
                    return $carry || call_user_func($checkMethod, $optionValue);
                }, false);
                $vars[$option] = $noQuotes
                    ? $optionValue
                    : '\'' . $optionValue . '\'';
            }
            $body .= $this->filterManager->template($template, ['variables' => $vars]);
        }
        return $body;
    }

    /**
     * Indent all of $body and wrap in version check
     *
     * @param $body
     */
    protected function wrapVersionChecker(&$body)
    {
        $body = preg_replace('/^/m', '    ', $body); // indent all body lines by 4 extra spaces
        $wrapBegin = <<<'CODE'
        if (version_compare($context->getVersion(), '{{var version}}') < 0) {

CODE;
        $wrapEnd = <<<'CODE'
        }

CODE;

        $body = $wrapBegin . $body . $wrapEnd;
    }

    /**
     * Set class properties based on metadata, return trimmed csv rows ready for import
     *
     * @param string $csvFilename
     * @return array
     * @throws \Exception
     */
    protected function parseCsvMetadata($csvFilename)
    {
        // If path is not absolute, assume relative to webroot
        if (strpos($csvFilename, DIRECTORY_SEPARATOR) !== 0) {
            $csvFilename = BP . DIRECTORY_SEPARATOR . $csvFilename;
        }
        $rows = array_map('str_getcsv', file($csvFilename));
        // Get column titles row
        foreach($rows as $row) {
            if (!in_array('name', $row) && !in_array('type', $row)) {
                array_shift($rows); // Shift off possible header rows
            } else {
                $columnTitles = array_shift($rows);
                break;
            }
        }
        // Sanitize attribute options and link with associated index from columns array
        foreach($this->attributeOptions as $attributeOption) {
            $index = array_search($attributeOption, $columnTitles);
            if ($index !== false) {
                $this->columnToOptionLink[$index] = $attributeOption;
            }
        }
        // Make sure name column exists
        $this->nameIndex = array_search('name', $columnTitles);
        if ($this->nameIndex === false) {
            throw new \Exception("Couldn't find 'name' column for attributes");
        }
        return $rows;
    }

    /**
     * @return string
     */
    protected function getAddAttributeTemplate()
    {
        $start = <<<'CODE'
        $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, '{{var attribute_code}}');
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            '{{var attribute_code}}',
            [

CODE;
        $options = '';
        foreach($this->columnToOptionLink as $option) {
            $options .= <<<"CODE"
                '$option' => {{var $option}},

CODE;
        }

        $end = <<<'CODE'
            ]
        );

CODE;

        return $start . $options . $end;
    }

    /**
     * Get header and footer in [$header, $footer] format
     *
     * @return string[]
     */
    protected function getHeaderFooter()
    {
        $header = <<<'CODE'
<?php
/**
 * @package     BlueAcorn\{{var module_name}}
 * @version     {{var version}}
 * @author      Blue Acorn, Inc. <code@blueacorn.com>
 * @copyright   Copyright © 2016 Blue Acorn, Inc.
 */
namespace BlueAcorn\{{var module_name}}\Setup;

use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\{{var interface}};
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class {{var class}} implements {{var interface}}
{
    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(EavSetupFactory $eavSetupFactory)
    {
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function {{var method}}(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);

CODE;
        $footer = <<<'CODE'
    }
}
CODE;

        return [$header, $footer];
    }

    /**
     * Check if value is a class constant
     *
     * @param $value
     * @return int
     */
    protected function _isClassConstant($value)
    {
        return preg_match(self::CLASS_CONSTANT_PATTERN, $value);
    }

    /**
     * Check if value is a boolean string
     *
     * @param $value
     * @return bool
     */
    protected function _isBool($value)
    {
        return in_array($value, ['true', 'false']);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName(self::COMMAND_PRODUCT_SCRIPT_GENERATOR);
        $this->setDescription('Generate product attribute install script');
        $this->addArgument(
            self::ARGUMENT_CSV_FILE,
            null,
            'Csv file with attribute data'
        );
        $this->addOption(
            self::OPTION_VERSION,
            null,
            InputOption::VALUE_REQUIRED,
            'Wrap attribute creation in a version check'
        );
        $this->addOption(
            self::OPTION_MODULE_NAME,
            null,
            InputOption::VALUE_REQUIRED,
            'Specify module name for namespacing in output file (defaults to ProductIntegration)',
            self::DEFAULT_MODULE_NAME
        );
        $this->setHelp(
            <<<HELP
This command generates install/upgrade data scripts based on an input CSV file. See
this file:
https://docs.google.com/a/blueacorn.com/spreadsheets/d/1SZaGBvqH4zblVdxJ39NHBdk_HAag9zipW2nSRx5GrnQ/edit?usp=sharing
for more information.

By default an InstallData.php file is generated. Use the --setup-version option to generate an UpgradeData.php file
with attribute creation wrapped in a version check. For example to create an upgrade file for version 1.2.0:

      <comment>%command.full_name% dataFile.csv --setup-version=1.2.0</comment>

If the import csv does not specify an absolute path, it is assumed relative to the webroot.
HELP
        );
        parent::configure();
    }
}
