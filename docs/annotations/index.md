# Annotations

## Annotations reference
- [Annotations reference](annotations-reference.md)

## Annotations & type inheritance

As PHP classes naturally support inheritances (and so is the annotation reader), it doesn't make sense to allow classes to use the "inherits" option.  
The type will inherits the annotations declared on parent classes properties and methods. The annotation on the class itself will not be herited.