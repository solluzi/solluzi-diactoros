<?php

declare(strict_types=1);

namespace Solluzi\Diactoros\Response;

use Psr\Http\Message\ResponseInterface;

/**
 * Emissor SAPI personalizado para respostas PSR-7.
 * Esta classe emite cabeçalhos e o corpo de uma resposta HTTP.
 */
class SapiEmitter
{
    /**
     * Emite a resposta HTTP para o SAPI.
     *
     * @param ResponseInterface $response A resposta PSR-7 a ser emitida.
     */
    public function emit(ResponseInterface $response): void
    {
        $this->emitHeaders($response);
        $this->emitStatusLine($response);
        $this->emitBody($response);
    }

    /**
     * Emite a linha de status HTTP.
     *
     * @param ResponseInterface $response A resposta a ser emitida.
     */
    private function emitStatusLine(ResponseInterface $response): void
    {
        $reasonPhrase    = $response->getReasonPhrase();
        $statusCode      = $response->getStatusCode();
        $protocolVersion = $response->getProtocolVersion();

        // Constrói a linha de status HTTP.
        $statusLine = sprintf(
            'HTTP/%s %d%s',
            $protocolVersion,
            $statusCode,
            ($reasonPhrase ? ' ' . $reasonPhrase : '')
        );

        // Verifica se os cabeçalhos já foram enviados para evitar avisos.
        if (!headers_sent()) {
            header($statusLine, true, $statusCode);
        }
    }

    /**
     * Emite os cabeçalhos da resposta.
     *
     * @param ResponseInterface $response A resposta a ser emitida.
     */
    private function emitHeaders(ResponseInterface $response): void
    {
        // Emite cada cabeçalho.
        foreach ($response->getHeaders() as $name => $values) {
            $name = str_replace('-', ' ', $name);
            $name = ucwords($name);
            $name = str_replace(' ', '-', $name);

            foreach ($values as $value) {
                // Adiciona o cabeçalho. O segundo argumento 'false' significa que não substitui
                // um cabeçalho existente com o mesmo nome, o que é importante para cabeçalhos
                // que podem ter múltiplos valores (ex: Set-Cookie).
                if (!headers_sent()) {
                    header(sprintf('%s: %s', $name, $value), false);
                }
            }
        }
    }

    /**
     * Emite o corpo da resposta.
     *
     * @param ResponseInterface $response A resposta a ser emitida.
     */
    private function emitBody(ResponseInterface $response): void
    {
        $body = $response->getBody();

        // Se o corpo não for pesquisável ou legível, não podemos emitir.
        if ($body->isSeekable()) {
            $body->rewind();
        }

        // Emite o corpo em blocos para lidar com grandes arquivos sem esgotar a memória.
        while (!$body->eof()) {
            echo $body->read(8192); // Lê 8KB por vez.
        }
    }
}
