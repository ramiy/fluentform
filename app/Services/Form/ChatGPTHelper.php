<?php

namespace FluentForm\App\Services\Form;

use FluentForm\App\Models\Form;
use FluentForm\App\Models\FormMeta;
use FluentForm\Framework\Helpers\ArrayHelper as Arr;

class ChatGPTHelper extends FormService
{
    public function generateAndSaveForm($req)
    {
        $form = $this->generateForm($req);
        $allFields = $this->getDefaultFields();
        $fluentFormFields = [];
        $fields = Arr::get($form, 'fields', []);
        
        foreach ($fields as $field) {
            if ($inputKey = $this->resolveInput($field)) {
                $fluentFormFields[] = $this->processField($inputKey, $field, $allFields);
            }
        }
        $fluentFormFields = $this->maybeAddPayments($fluentFormFields, $allFields);
      
        $title = Arr::get($form, 'title', '');
        return $this->saveForm($fluentFormFields, $title);
    }
    
    protected function generateForm($req)
    {
        $startingQuery = "Create a form for ";
        $query = \FluentForm\Framework\Support\Sanitizer::sanitizeTextField(Arr::get($req, 'query'));
        if (empty($query)) {
            throw new \Exception(__('Query is empty!'));
        }
        
        $additionalQuery = \FluentForm\Framework\Support\Sanitizer::sanitizeTextField(Arr::get($req, 'additional_query'));
        
        if ($additionalQuery) {
            $query .= "\n including questions for information like  " . $additionalQuery . ".";
        }
//        $query .= " \n return as json fluentform format and code only,
//                    don't include text in response, declare inside fields array,if payment key included add a field type with payment key,
//                    use type key as field type and form title as title key
//                    ";

        $query .= "\nField includes 'type', 'name', 'label', 'placeholder', 'required' status, 'options' if has options will be format 'label' 'value' pair.
        \nIf has field like full name, first name, last name, field key type will be 'name'. If has field like phone, field key type will be 'phone'.
        \nIf has field like payment, field key type will be 'payment'.
        \nAdd 'title' key to define the form title.
        \nReturn the form data in JSON format, adhering to FluentForm's structure. Only include the form fields inside the 'fields' array.";

        $args = [
            "role"    => 'system',
            "content" => $startingQuery . $query,
        ];
        
        $token = Arr::get(get_option('_fluentform_openai_settings'), 'access_token');
        $result = (new \FluentFormPro\classes\Chat\ChatFieldController(wpFluentForm()))->makeRequest($token, $args);
        $response = trim(Arr::get($result, 'choices.0.message.content'), '"');
        $response = json_decode($response, true);

        if (is_wp_error($response) || empty($response['fields'])) {
            wp_send_json_error('Failed :'.json_encode($response), 422);
        }
        
        return $response;
    }
    
    protected function getDefaultFields()
    {
        $components = $this->components('');
        //todo remove disabled elements
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
        $matchedField = $this->getElementByType($allFields, $inputKey);
        $matchedField['uniqElKey'] = "el_" . uniqid();

        if ($label = Arr::get($field, 'label')) {
            $required = Arr::isTrue($field, 'required');
            if (isset($matchedField['settings']['label'])) {
                $matchedField['settings']['label'] = $label;
                if (isset($matchedField['settings']['validation_rules']['required']['value'])) {
                    $matchedField['settings']['validation_rules']['required']['value'] = $required;
                }
            }

            $placeholder = Arr::get($field, 'placeholder');
            if ($placeholder) {
                if (isset($matchedField['attributes']['placeholder'])) {
                    $matchedField['attributes']['placeholder'] = $placeholder;
                } elseif (isset($matchedField['settings']['placeholder'])) {
                    $matchedField['settings']['placeholder'] = $placeholder;
                }
            }

            if (isset($matchedField['fields'])) {
                $subFields = $matchedField['fields'];
                $subNames = explode(" ", $label);
                if (count($subNames) > 1) {
                    $counter = 0;
                    foreach ($subFields as $subFieldkey => $subFieldValue) {
                        if (Arr::get($subNames, $counter)) {
                            if (Arr::has($subFieldValue, 'settings.visible') && !Arr::isTrue($subFieldValue, 'settings.visible')) {
                                continue;
                            }
                            if (isset($subFieldValue['settings']['label'])) {
                                $subFields[$subFieldkey]['settings']['label'] = Arr::get($subNames, $counter);
                                $subFields[$subFieldkey]['settings']['validation_rules']['required']['value'] = $required;
                            }
                            $counter++;
                        }
                    }
                }
                $matchedField['fields'] = $subFields;
            }
        }
        
        if ($options = $this->getOptions(Arr::get($field, 'options'))) {
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
        foreach ($options as $key => $option) {
            if (is_string($option) || is_numeric($option)) {
                $value = $label = $option;
            } elseif (is_array($option)) {
                $label = Arr::get($option, 'label');
                $value = Arr::get($option, 'value');
            } else {
                continue;
            }
            if (!$value || !$label) {
                $value = $value ?? $label;
                $label = $label ?? $value;
            }
            if (!$value || !$label) {
                continue;
            }
            $formattedOptions[] = [
                'label' => $label,
                'value' => $value,
            ];
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

    protected function maybeAddPayments($fluentFormFields, $allFields)
    {
        $paymentElements = ['payment_method', 'multi_payment_component'];
        $foundElements = [];
        if (empty($fluentFormFields)) {
            return [];
        }
        foreach ($fluentFormFields as $item) {
            if (in_array($item["element"], $paymentElements)) {
                $foundElements[] = $item["element"];
            }
        }
        // Find the elements in $paymentElements that are not in $foundElements
        $remainingElements = array_diff($paymentElements, $foundElements);
        $formPaymentElm = [];
        if ($foundElements && !empty($remainingElements)) {
            foreach ($remainingElements as $elmKey) {
                $formPaymentElm[] = $this->getElementByType($allFields, $elmKey);
            }
        }
        return array_merge($fluentFormFields, $formPaymentElm);
    }
    
}
