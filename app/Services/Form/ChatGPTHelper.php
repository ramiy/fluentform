<?php

namespace FluentForm\App\Services\Form;

use FluentForm\App\Models\Form;
use FluentForm\App\Models\FormMeta;
use FluentForm\Framework\Helpers\ArrayHelper;
use FluentForm\Framework\Support\Arr;

class ChatGPTHelper extends FormService
{
    public function generateAndSaveForm($req)
    {
        $form = $this->generateForm($req);
        $allFields = $this->getDefaultFields();
        $fluentFormFields = [];
        $fields = $form['fields'];
        $test= [];
        foreach ($fields as $field) {
            if ($inputKey = $this->resolveInput($field)) {
                $test[$inputKey] =$field;
                $fluentFormFields[] = $this->processField($inputKey, $field, $allFields);
            }
        }
    
        $title = $form['title'] ?? '';
        return $this->saveForm($fluentFormFields, $title);
    }
    
    protected function generateForm($req)
    {
        $startingQuery = "Create a form for ";
        $query = \FluentForm\Framework\Support\Sanitizer::sanitizeTextField(Arr::get($req, 'query'));
        if (empty($query)) {
            throw new \Exception(__('Query is empty!'));
        }
        
        $additionalQuery = \FluentForm\Framework\Support\Sanitizer::sanitizeTextField(Arr::get($req,
            'additional_query'));
        
        if ($additionalQuery) {
            $query .= "\n including questions for information like  " . $additionalQuery;
        }
        $query .= " \n return as json fluentform format and code only,
                    don't include text in response, declare inside fields array,if payment key included add a field type with payment key,
                    use type key as field type and form title as title key";
        $args = [
            "role"    => 'system',
            "content" => $startingQuery . $query,
        ];
        
        $token = ArrayHelper::get(get_option('_fluentform_openai_settings'), 'access_token');
        $result = (new \FluentFormPro\classes\Chat\ChatFieldController(wpFluentForm()))->makeRequest($token, $args);
        $response = trim(ArrayHelper::get($result, 'choices.0.message.content'), '"');
        $response = json_decode($response, true);

//        $response = [
//            "title"       => "T-shirt Registration Form",
//            "description" => "Please fill out the following information to register for a T-shirt.",
//            "fields"      => [
//                [
//                    "id"       => "name",
//                    "type"     => "text",
//                    "label"    => "Full Name",
//                    "required" => true
//                ],
//                [
//                    "id"       => "email",
//                    "type"     => "email",
//                    "label"    => "Email",
//                    "required" => true
//                ],
//                [
//                    "id"       => "phone",
//                    "type"     => "tel",
//                    "label"    => "Phone Number",
//                    "required" => true
//                ],
//                [
//                    "id"       => "size",
//                    "type"     => "select",
//                    "label"    => "T-shirt Size",
//                    "required" => true,
//                    "options"  => ["S" => "Small", "M" => "Medium", "L" => "Large", "XL" => "Extra Large"]
//                ],
//                [
//                    "id"       => "color",
//                    "type"     => "select",
//                    "label"    => "T-shirt Color",
//                    "required" => true,
//                    "options"  => ["red" => "Red", "blue" => "Blue", "green" => "Green", "black" => "Black"]
//                ]
//            ]
//        ];
        if (is_wp_error($response) || empty($response['fields'])) {
            wp_send_json_error('Failed', 422);
        }
        
        return $response;
    }
    
    protected function getDefaultFields()
    {
        $components = $this->components('');
        $disabledComponents = $this->getDisabledComponents();
        return array_merge($components['general'], $components['advanced'],$components['payments']);
    }
    
    public  function getElementByType($allFields, $type) {
        foreach ($allFields as $element) {
                if (isset($element['element']) && $element['element'] === $type) {
                    return $element;
                }
        }
        return null;
    }
    
    protected function processField($inputKey, $field, $allFields)
    {
        $matchedField = $this->getElementByType($allFields,$inputKey);
        $matchedField['uniqElKey'] = "el_" . uniqid();
        
        $label = ArrayHelper::get($field, 'label');
        $required = ArrayHelper::isTrue($field, 'required');
        $options = ArrayHelper::get($field, 'options');
        
        if ($label) {
            if (isset($matchedField['settings']['label'])) {
                $matchedField['settings']['label'] = $label;
                if (isset($matchedField['settings']['validation_rules']['required']['value'])) {
                    $matchedField['settings']['validation_rules']['required']['value'] = $required;
                }
            } elseif (isset($matchedField['fields'])) {
                $subFields = $matchedField['fields'];
                $subNames = explode($label);
                if (count($subNames) > 1) {
                    $counter = 0;
                    foreach ($subFields as $subFieldkey => $subFieldValue) {
                        if (isset($subFieldValue['settings']['label']) && ArrayHelper::get($subNames, $counter)) {
                            $subFields[$subFieldkey]['settings']['label'] = ArrayHelper::get($subNames, $counter);
                            $subFields[$subFieldkey]['settings']['validation_rules']['required']['value'] = $required;
                            $counter++;
                        }
                    }
                }
                $matchedField['fields'] = $subFields;
            }
        }
        
        if ($options) {
            $options = $this->getOptions($options);
            if (isset($matchedField['settings']['advanced_options'])) {
                $matchedField['settings']['advanced_options'] = $options;
            }
        }
        
        return $matchedField;
    }
    
    private function resolveInput($field)
    {
        $type = Arr::get($field, 'type');
        $searchTags = fluentformLoadFile('Services/FormBuilder/ElementSearchTags.php');
        $form =['type'=>''];
        $form = json_decode(json_encode($form));
        $searchTags = apply_filters('fluentform/editor_element_search_tags', $searchTags, $form);
        foreach ($searchTags as $inputKey => $tags) {
            if (array_search($type, $tags) !== false) {
                return $inputKey;
            }
        }
        return false;
    }
    
    public function getOptions($options = [])
    {
        $formattedOptions = [];
        if (empty($options) || !is_array($options)) {
            return $options;
        }
        foreach ($options as $key => $value) {
            $label = $value['label'] ??$value;
            $key = $value['value'] ??$key;
            $arr = [
                'label' => $label,
                'value' => $key,
            ];
            $formattedOptions[] = $arr;
        }
        
        return $formattedOptions;
    }
    
    protected function getBlankFormConfig()
    {
        $attributes = ['type' => 'form', 'predefined' => 'blank_form'];
        $customForm = Form::resolvePredefinedForm($attributes);
        $customForm['form_fields'] = json_decode($customForm['form_fields'], true);
        $customForm['form_fields']['submitButton'] = $customForm['form']['submitButton'];
        $customForm['form_fields'] = json_encode($customForm['form_fields']);
        return $customForm;
    }
    
    private function saveForm($formattedInputs, $title)
    {
        $customForm = $this->getBlankFormConfig();
        $fields = json_decode($customForm['form_fields'], true);
        $submitButton = $customForm['form']['submitButton'];
        $fields['form_fields']['fields'] = $formattedInputs;
        $fields['form_fields']['submitButton'] = $submitButton;
        $customForm['form_fields'] = json_encode($fields['form_fields']);
        $data = Form::prepare($customForm);
        $form = $this->model->create($data);
        $form->title = $title ?? $form->title . ' (Chatgpt#' . $form->id . ')';
        $form->save();
        
        $formMeta = FormMeta::prepare(['type' => 'form', 'predefined' => 'blank_form'], $customForm);
        FormMeta::store($form, $formMeta);
        
        do_action('fluentform/inserted_new_form', $form->id, $data);
        return $form;
    }
    
}
