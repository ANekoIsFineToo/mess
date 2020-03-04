<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class HomeControllerTest extends WebTestCase
{
    public function testNewThread(): void
    {
        // El cliente es inicializado, utilizando un usuario de las fixture
        $client = static::createClient([], [
            'PHP_AUTH_USER' => 'oliversox@example.org',
            'PHP_AUTH_PW'   => 'oliversox'
        ]);

        // Se solicita la página de inicio, y se confirma que es accesible
        $homeCrawler = $client->request('GET', '/');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        // El formulario para la creación de una nueva conversación es obtenido
        $newThreadForm = $homeCrawler->selectButton('newThreadFormSubmit')->form();
        $newThreadFormName = $newThreadForm->getName();

        // Datos del formulario que posteriormente serán validados
        $title = 'Conversación de prueba';
        $content = 'Primer mensaje de la conversación';
        $filename = 'default-avatar.png';

        // Se introducen los datos en el formulario
        $newThreadForm["{$newThreadFormName}[title]"] = $title;
        $newThreadForm["{$newThreadFormName}[message][content]"] = $content;
        $newThreadForm["{$newThreadFormName}[message][attachments][0]"]->upload(__DIR__ . '/' . $filename);

        // El primer miembro de la lista de miembros disponibles es seleccionado
        $memberOptions = $homeCrawler->filter('#new_thread_form_members')->children('option');
        $firstMemberName = $memberOptions->text();
        $firstMemberValue = $memberOptions->attr('value');

        if ($firstMemberValue !== null)
        {
            // Si este primer miembro seleccionado está disponible se añade como integrante
            $newThreadForm["{$newThreadFormName}[members]"]->select($firstMemberValue);
        }

        // Una vez rellenado el formulario es enviado, se comprueba que el envío provoca una redirección
        // Si no se produjese una redirección se debería a algún error o dato incorrecto
        $client->submit($newThreadForm);
        $this->assertTrue($client->getResponse()->isRedirect());

        // Se sigue la redirección, y comprueba que la respuesta de esta es accesible
        $threadCrawler = $client->followRedirect();

        // El nombre de la conversación es validado
        self::assertSelectorTextContains('#dropdownMainMenu', $title);

        // El contenido de la conversación es válido
        self::assertSelectorTextContains('.thread-content .card-body p.card-text', $content);

        // Existe un botón para descargar el fichero enviado
        self::assertSelectorTextContains('.thread-content .card-body a.btn', $filename);

        if ($firstMemberValue !== null)
        {
            // Si se ha enviado un miembro al crear la conversación se valida que este en la lista de miembros
            $this->assertGreaterThan(
                0,
                $threadCrawler->filter(".members-list a.h5:contains(${firstMemberName})")->count()
            );
        }
    }
}
