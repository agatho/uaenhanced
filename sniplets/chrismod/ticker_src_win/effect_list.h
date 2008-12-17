
#ifndef _EFFECTS_H_ 
#define _EFFECTS_H_ 

#define MAX_EFFECT 28

#include <stdlib.h>
#include <string.h>
  
typedef struct EffectType{
  int    effectID;
  char*  name;
  char*  dbFieldName;
  char*  description;   
} EffectType;


extern EffectType effectTypeList[MAX_EFFECT];

#endif
