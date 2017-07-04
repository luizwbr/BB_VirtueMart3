# BB_VirtueMart3
Plugin para emissão de boletos do Banco do Brasil para VirtueMart 3.x

------------------------
* Virtuemart 3.x / Joomla 3.x

Tutorial
-------
Passo 1 - Habilite o plugin em Administrar Plugins
Passo 2 - Instale Plugin por esta tela Métodos de pagamento
Passo 2.1 - Clique em Novo Método de Pagamento e preencha as informações:
* Nome do Pagamento: Banco do Brasil
* Publicado: Sim
* Descrição do pagamento: Pague com Boleto bancário ou Transferência Bancária
* Método de pagamento: Banco do Brasil
* Grupo de Compradores: -default-, -anonymous-
Passo 2.2 - Clique em Salvar.
Passo 3 - Na aba configurações, preencha os dados:
* Logotipos:
* Número do convênio ( teste )
* Número do convênio cobrança ( teste )
* Dias de vencimento ( teste )
* Número do convênio ( produção )
* Número do convênio cobrança ( produção )
* Dias de vencimento ( produção )
* Mensagem para boleto
* Url completa do arquivo de retorno ( /home/site/public_html/ )
* Configuração Retorno automático ( Urls para automização do processo de pagamento )
* Aprovado: Status do Pedido quando Aprovada a transação
* Cancelado: Status do Pedido quando Cancelada a transação
* Aguardando Pagto: Status do Pedido quando transação Pendente

Configuração do retorno automático ( arquivo do banco ).
-- Visualizar
http://seusite.com.br/index.php?option=com_virtuemart&view=pluginresponse&task=pluginresponsereceived&pm=4&boleto=1

-- Atualizar
http://seusite.com.br/index.php?option=com_virtuemart&view=pluginresponse&task=pluginresponsereceived&pm=4&boleto=1&atualiza=1
Licença

-------

Copyright Weber TI.

Licensed under the Apache License, Version 2.0 (the "License"); you may not use this file except in compliance with the License. You may obtain a copy of the License at

http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software distributed under the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the License for the specific language governing permissions and limitations under the License.


Dúvidas
----------

https://github.com/luizwbr/BB_VirtueMart3/issues

Contribuições
-------------

Achou e corrigiu um bug ou tem alguma feature em mente e deseja contribuir?

* Faça um fork
* Adicione sua feature ou correção de bug (git checkout -b my-new-feature)
* Commit suas mudanças (git commit -am 'Added some feature')
* Rode um push para o branch (git push origin my-new-feature)
* Envie um Pull Request
* Obs.: Adicione exemplos para sua nova feature. Se seu Pull Request for relacionado a uma versão específica, o Pull Request não deve ser enviado para o branch master e sim para o branch correspondente a versão.
