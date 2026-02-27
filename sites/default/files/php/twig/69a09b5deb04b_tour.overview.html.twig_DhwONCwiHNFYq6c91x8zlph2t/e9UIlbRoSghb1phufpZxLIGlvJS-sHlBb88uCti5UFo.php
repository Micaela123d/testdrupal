<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Extension\SandboxExtension;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Source;
use Twig\Template;
use Twig\TemplateWrapper;

/* @help_topics/tour.overview.html.twig */
class __TwigTemplate_490cc7f3e4a86b48386f512ba977072b extends Template
{
    private Source $source;
    /**
     * @var array<string, Template>
     */
    private array $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
        $this->sandbox = $this->extensions[SandboxExtension::class];
        $this->checkSecurity();
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        // line 6
        yield "<h2>";
        yield t("Goal", array());
        yield "</h2>
<p>";
        // line 7
        yield t("Take a tour of an administrative page.", array());
        yield "</p>
<h2>";
        // line 8
        yield t("What are tours?", array());
        yield "</h2>
<p>";
        // line 9
        yield t("The core Tour module provides users with <em>tours</em>, which are guided tours of the administrative interface. Each tour starts on a particular administrative page, and consists of one or more <em>tips</em> that highlight elements of the page, guide you through a workflow, or explain key concepts. Users need <em>Access tour</em> permission to view tours, and JavaScript must be enabled in their browsers.", array());
        yield "</p>
<h2>";
        // line 10
        yield t("Steps", array());
        yield "</h2>
<ol>
  <li>";
        // line 12
        yield t("Make sure that the core Tour module is installed, and that you have a role with the <em>Access tour</em> permission. Also, make sure that a toolbar module is installed (either the core Toolbar module or a contributed module replacement).", array());
        yield "</li>
  <li>";
        // line 13
        yield t("Visit an administrative page that has a tour, such as the edit view page provided by the core Views UI module.", array());
        yield "</li>
  <li>";
        // line 14
        yield t("Click the <em>Tour</em> button at the right end of the toolbar (left end for right-to-left languages). The first tip of the tour should appear.", array());
        yield "</li>
  <li>";
        // line 15
        yield t("Click the <em>Next</em> button to advance to the next tip, and <em>End tour</em> at the end to close the tour.", array());
        yield "</li>
</ol>";
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "@help_topics/tour.overview.html.twig";
    }

    /**
     * @codeCoverageIgnore
     */
    public function isTraitable(): bool
    {
        return false;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getDebugInfo(): array
    {
        return array (  78 => 15,  74 => 14,  70 => 13,  66 => 12,  61 => 10,  57 => 9,  53 => 8,  49 => 7,  44 => 6,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "@help_topics/tour.overview.html.twig", "C:\\xampp\\htdocs\\drupal\\core\\modules\\tour\\help_topics\\tour.overview.html.twig");
    }
    
    public function checkSecurity()
    {
        static $tags = ["trans" => 6];
        static $filters = [];
        static $functions = [];

        try {
            $this->sandbox->checkSecurity(
                ['trans'],
                [],
                [],
                $this->source
            );
        } catch (SecurityError $e) {
            $e->setSourceContext($this->source);

            if ($e instanceof SecurityNotAllowedTagError && isset($tags[$e->getTagName()])) {
                $e->setTemplateLine($tags[$e->getTagName()]);
            } elseif ($e instanceof SecurityNotAllowedFilterError && isset($filters[$e->getFilterName()])) {
                $e->setTemplateLine($filters[$e->getFilterName()]);
            } elseif ($e instanceof SecurityNotAllowedFunctionError && isset($functions[$e->getFunctionName()])) {
                $e->setTemplateLine($functions[$e->getFunctionName()]);
            }

            throw $e;
        }

    }
}
