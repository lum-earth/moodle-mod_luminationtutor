<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Spanish language strings for mod_luminationtutor.
 *
 * @package    mod_luminationtutor
 * @copyright  2026 Lumination AI <https://lumination.ai>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['askplaceholder'] = 'Haz una pregunta sobre el material del curso';
$string['dailylimit'] = 'Preguntas por estudiante y día';
$string['dailylimit_error'] = 'Introduce un número de 1 o más.';
$string['dailylimit_help'] = 'Cada pregunta consume créditos de AI Tutor. Cuando un estudiante llega a este número, puede volver a preguntar al día siguiente.';
$string['dailylimitreached'] = 'Hoy has hecho {$a} preguntas, el límite diario. Mañana podrás preguntar más.';
$string['document'] = 'Documento del curso';
$string['document_help'] = 'El archivo PDF, Word (.docx) o de texto a partir del cual responde el tutor, por ejemplo los apuntes de esta sección. Si lo sustituyes, las conversaciones de los estudiantes empiezan de nuevo con el documento nuevo.';
$string['emptymessage'] = 'Escribe primero una pregunta.';
$string['enabled'] = 'Tutor';
$string['enabled_label'] = 'Los estudiantes pueden hacer preguntas al tutor';
$string['intro'] = 'Haz una pregunta sobre este material. El tutor responde a partir del documento que ha añadido tu profesor.';
$string['luminationtutor:addinstance'] = 'Añadir un Lumination AI Tutor';
$string['luminationtutor:use'] = 'Hacer preguntas a Lumination AI Tutor';
$string['luminationtutor:view'] = 'Ver un Lumination AI Tutor';
$string['messagetoolong'] = 'Las preguntas pueden tener hasta {$a} caracteres.';
$string['modulename'] = 'Lumination AI Tutor';
$string['modulename_help'] = 'Lumination AI Tutor permite a los estudiantes hacer preguntas sobre un documento que añades, como los apuntes de una sección, y las responde a partir de ese documento. Cada estudiante tiene su propia conversación, y tú decides cuántas preguntas puede hacer cada estudiante al día. Las preguntas las responde la API de AI Tutor de Lumination AI. No se envían los nombres ni los correos electrónicos de los estudiantes.';
$string['modulenameplural'] = 'Lumination AI Tutors';
$string['nodocument'] = 'El tutor aún no tiene un documento del curso.';
$string['nodocumentteacher'] = 'Añade un documento del curso en la configuración de esta actividad. El tutor responde a partir de él.';
$string['noprovider'] = 'El proveedor Lumination AI Provider no está configurado en este sitio. Un administrador puede añadirlo en Administración del sitio > IA > Proveedores de IA con una clave de la API de AI Tutor.';
$string['noreply'] = 'La API de AI Tutor no ha aceptado la pregunta.';
$string['pluginadministration'] = 'Administración de Lumination AI Tutor';
$string['pluginname'] = 'Lumination AI Tutor';
$string['privacy:metadata:aitutor'] = 'Las preguntas se envían a la API de AI Tutor de Lumination AI, que las responde a partir del documento del curso añadido por el profesor. No se envían el nombre ni el correo electrónico del usuario.';
$string['privacy:metadata:aitutor:conversationid'] = 'El ID de la conversación del usuario, para que el tutor recuerde las preguntas anteriores.';
$string['privacy:metadata:aitutor:message'] = 'El texto de cada pregunta.';
$string['privacy:metadata:conv'] = 'La conversación de cada usuario con el tutor de una actividad.';
$string['privacy:metadata:conv:conversationid'] = 'El ID de la conversación en la API de AI Tutor.';
$string['privacy:metadata:conv:timecreated'] = 'Cuándo hizo el usuario su primera pregunta al tutor.';
$string['privacy:metadata:conv:userid'] = 'El ID del usuario.';
$string['privacy:metadata:msg'] = 'Las preguntas que un usuario hizo al tutor y sus respuestas.';
$string['privacy:metadata:msg:message'] = 'La pregunta.';
$string['privacy:metadata:msg:reply'] = 'La respuesta del tutor.';
$string['privacy:metadata:msg:timecreated'] = 'Cuándo se hizo la pregunta.';
$string['privacy:metadata:msg:userid'] = 'El ID del usuario que preguntó.';
$string['remaining'] = 'Hoy puedes hacer {$a} preguntas más';
$string['replyfailed'] = 'El tutor no ha podido responder a esta pregunta. Vuelve a preguntar, por favor.';
$string['send'] = 'Preguntar';
$string['thinking'] = 'El tutor está escribiendo una respuesta...';
$string['tutornotavailable'] = 'El tutor no está disponible en este momento. Si sigue así, avisa a tu profesor.';
$string['tutoroff'] = 'El tutor está desactivado.';
$string['tutoroffteacher'] = 'El tutor está desactivado. Los estudiantes no pueden hacer preguntas hasta que lo actives en la configuración de esta actividad.';
$string['tutorsettings'] = 'Tutor';
$string['tutorunavailable'] = 'El tutor no está disponible en este momento. Inténtalo más tarde.';
$string['usage'] = '{$a->messages} preguntas de {$a->students} estudiantes hoy, {$a->total} en total.';
$string['waitforreply'] = 'Espera la respuesta a tu última pregunta antes de hacer otra.';
$string['yourquestion'] = 'Tu pregunta';
