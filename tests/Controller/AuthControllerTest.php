<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AuthControllerTest extends WebTestCase
{
    public function testLogin(): void
    {
        // El cliente es inicializado
        $client = static::createClient();

        // Se solicita la página de inicio de sesión, y se confirma que es accesible
        $loginCrawler = $client->request('GET', '/login');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        // El formulario de inicio de sesión es obtenido y rellenado con los datos falsos de la fixture
        $loginForm = $loginCrawler->selectButton('loginFormSubmit')->form();
        $loginForm['login_form[email]'] = 'oliversox@example.org';
        $loginForm['login_form[password]'] = 'oliversox';

        // Una vez rellenado el formulario es enviado, se comprueba que el envío provoca una redirección
        // Si no se produjese una redirección se debería a algún error o dato incorrecto
        $client->submit($loginForm);
        $this->assertTrue($client->getResponse()->isRedirect());

        // Se sigue la redirección, y comprueba que la respuesta de esta es accesible
        $homeCrawler = $client->followRedirect();
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        // Por último, se comprueba que la redirección lleva a la página principal
        $this->assertEquals('http://localhost/', $homeCrawler->getUri());
    }
}
