<?php 
namespace sigawa\mvccore\view;

interface ViewRendererInterface
{
    public function render(string $viewPath, array $params): string;
}
